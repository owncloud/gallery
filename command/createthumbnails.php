<?php
/**
 * ownCloud - gallery
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Olivier Paroz <owncloud@interfasys.ch>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @copyright Olivier Paroz 2015
 */

namespace OCA\Gallery\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

// Does not exist in OCP
use OC\Files\Cache\ChangePropagator;
use OC\Files\Storage\Storage;
// Does not exist in OCP and needed by ChangePropagator
use OC\Files\View;
// Methods we need are not available in OCP
use OC\Preview;

use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\IUser;
use OCP\Files\IRootFolder;
// 8.2+ only
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Encryption\IManager;
use OCP\Lock\ILockingProvider;

use OCA\Gallery\Service\ConfigService;

/**
 * Class CreateThumbnails
 *
 * Creates missing thumbnails of visual media types supported by the system
 *
 * @package OCA\Gallery\Command
 */
class CreateThumbnails extends Thumbnails {

	/** @var GalleryScanner */
	private $scanner;
	/** @var IManager */
	private $encryptionManager;
	/** @var ChangePropagator */
	private $propagator;
	/** @var int */
	private $thumbnailWidth = 400;
	/** @var int */
	private $thumbnailHeight = 200;
	/** @var bool */
	private $regenerate = false;

	/**
	 * Constructor
	 *
	 * @param IDBConnection $database
	 * @param IUserManager $userManager
	 * @param IRootFolder $rootFolder
	 * @param IMountManager $mountManager
	 * @param ConfigService $configService
	 * @param IManager $encryptionManager
	 * @param ILockingProvider $lockingProvider
	 */
	public function __construct(
		IDBConnection $database,
		IUserManager $userManager,
		IRootFolder $rootFolder,
		IMountManager $mountManager,
		ConfigService $configService,
		IManager $encryptionManager,
		ILockingProvider $lockingProvider
	) {
		$this->encryptionManager = $encryptionManager;
		$this->propagator = new ChangePropagator(new View(''));
		$this->scanner = new GalleryScanner($database, $this->propagator, $lockingProvider);

		parent::__construct($userManager, $rootFolder, $mountManager, $configService);
	}

	/**
	 * Generates a thumbnail if there isn't one in the cache yet
	 *
	 * The first step is to make sure the path we've received is within the folder we're scanning
	 * since events are fired every time a file is scanned
	 *
	 * @param string $path
	 * @param IMountPoint $mount
	 * @param string $user
	 * @param OutputInterface $output
	 */
	public function generateMissingThumbnail(
		$path, IMountPoint $mount, $user, OutputInterface $output
	) {
		$path = $mount->getMountPoint() . $path;
		$pathFolders = $this->isPathAllowed($user, $path);
		$cancelled = $this->hasBeenInterrupted();
		if ($pathFolders && !$cancelled) {
			$newFolders = array_slice($pathFolders, 3);
			$pathFromVirtualRoot = ltrim(implode('/', $newFolders), '/');
			// Make sure we really have a file
			/** @type File|Folder $node */
			$node = $this->rootFolder->get($path);
			if ($this->isFile($node)) {
				$this->filesCounter++;
				$this->lastFile = $node->getPath();
				$this->createThumbnailForSupportedMediaType(
					$user, $node, $path, $pathFromVirtualRoot, $output
				);
			}
		}
	}

	/**
	 * Adds one to the total count of scanned folders
	 *
	 * @param string $path
	 * @param \OC\Files\Mount\MountPoint $mount
	 * @param string $user
	 * @param OutputInterface $output
	 */
	public function countFolders($path, IMountPoint $mount, $user, OutputInterface $output
	) {
		$path = $mount->getMountPoint() . $path;
		if ($this->isPathAllowed($user, $path)) {
			$this->foldersCounter++;
		}
	}

	/**
	 * Configures the command and describes parameters
	 */
	protected function configure() {
		$this->setName('gallery:create-thumbnails')
			 ->setDescription('creates thumbnails for supported visual media files')
			 ->addArgument(
				 'user_id',
				 InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
				 'will rescan all files of the given user(s) located in the files folder and create thumbnails of visual media files'
			 )
			 ->addOption(
				 'path',
				 'p',
				 InputArgument::OPTIONAL,
				 'limit rescan to this path, eg. --path="/alice/files/Holidays", the user_id is determined by the path and the user_id parameter and --all are ignored'
			 )
			 ->addOption(
				 'quiet',
				 'q',
				 InputOption::VALUE_NONE,
				 'suppress output'
			 )
			 ->addOption(
				 'regenerate',
				 'r',
				 InputOption::VALUE_NONE,
				 'forces the re-generation of thumbnails'
			 )
			 ->addOption(
				 'all',
				 null,
				 InputOption::VALUE_NONE,
				 'will rescan all files folders of all known users and create thumbnails of visual media files'
			 );
	}

	/**
	 * Executes the current command.
	 *
	 * This method is not abstract because you can use this class
	 * as a concrete class. In this case, instead of defining the
	 * execute() method, you set the code to execute by passing
	 * a Closure to the setCode() method.
	 *
	 * @param InputInterface $input An InputInterface instance
	 * @param OutputInterface $output An OutputInterface instance
	 *
	 * @return int|null null or 0 if everything went fine, or an error code
	 *
	 * @throws \Exception
	 * @see    setCode()
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		if ($this->encryptionManager->isEnabled() === true) {
			throw new ForbiddenCommandException(
				'We cannot create thumbnails if server side encryption is enabled'
			);
		}

		$this->initTools();

		$userInputErrorMsg =
			"Please specify the user id to scan, \"--all\" to scan for all users or \"--path=...\"";
		$pathInputErrorMsg =
			"The path given in \"--path=...\" does not start with \"/user_id/files\" eg. --path=\"/alice/files/Holidays\"";
		try {
			list($inputPath, $users, $regenerate) =
				$this->parseParameters($input, $userInputErrorMsg, $pathInputErrorMsg, $output);
		} catch (CommandException $exception) {
			$message = $exception->getMessage();
			$output->writeln("<error>$message</error>");

			return;
		}

		$this->regenerate = $regenerate;

		$this->scanAndGenerateMissingThumbnails($users, $inputPath, $output);

		$this->presentResults($output);
	}

	/**
	 * @inherit
	 */
	protected function parseParameters(
		InputInterface $input, $userInputErrorMsg, $pathInputErrorMsg, OutputInterface $output
	) {
		$params = parent::parseParameters($input, $userInputErrorMsg, $pathInputErrorMsg, $output);

		return array_merge($params, [$input->getOption('regenerate')]);
	}

	/**
	 * @inherit
	 */
	protected function presentResults(OutputInterface $output) {
		parent::presentResults($output);
		$headers = [
			'Folders', 'Files', 'Supported images', 'New previews', 'Failed previews',
			'Elapsed time',
			'Total size'
		];

		if ($this->filesCounter > 0) {
			$this->showSummary($headers, null, $output);
		}

		$headers = ['List of failed previews'];
		$this->showFailed($headers, $output);
	}

	/**
	 * Scans a given user's specific mounted storage and updates the cache
	 *
	 * @param Storage $storage
	 * @param IMountPoint $mount
	 * @param string $path
	 * @param string $user
	 * @param OutputInterface $output
	 *
	 * @return array
	 * @see OC\Files\Utils\Scanner::scan
	 */
	protected function scanAndUpdateCache(
		$storage, IMountPoint $mount, $path, $user, OutputInterface $output
	) {
		$size = $this->scanner->scan(
			$storage,
			$mount,
			$path,
			$user,
			[$this, 'generateMissingThumbnail'],
			[$this, 'countFolders'],
			$output
		);

		return $size;
	}

	/**
	 * Scans and generates missing thumbnails for all given users
	 *
	 * @param IUser[]|string[] $users
	 * @param string $inputPath
	 * @param OutputInterface $output
	 */
	private function scanAndGenerateMissingThumbnails($users, $inputPath, OutputInterface $output) {
		foreach ($users as $user) {
			if (is_object($user)) {
				$user = $user->getUID();
			}
			// Only create thumbnails for files located in the user's "files" folder
			$path = $inputPath ? $inputPath : '/' . $user . '/files';
			if ($this->userManager->userExists($user)) {
				$this->scanForMissingThumbnails($user, $path, $output);
			} else {
				$output->writeln("<error>Unknown user $user</error>");
			}
		}
	}

	/**
	 * Scans a given user's filesystem
	 *
	 * Discovered files are received via listeners which will then generate the thumbnails
	 *
	 * @param string $user
	 * @param string $path
	 * @param OutputInterface $output
	 */
	private function scanForMissingThumbnails(
		$user, $path, OutputInterface $output
	) {
		try {
			$foldersMetaData =
				$this->performOperation(
					'scanAndUpdateCache', $user, $path, $output
				);

			// Propagates the changes to all parent folder
			$this->propagator->propagateChanges(time());
			foreach ($foldersMetaData as $metaData) {
				$this->folderSize += $metaData['size'];
			}
		} catch (CommandException $exception) {
			$message = $exception->getMessage();
			$output->writeln("<error>$message</error>");
		}
	}

	/**
	 * Makes sure we only create thumbnails for files located in the user's "files" folder
	 *
	 * This check is still needed here because events we listen to can return newly created
	 * thumbnails!
	 *
	 * @param string $user
	 * @param string $path
	 *
	 * @return array|bool
	 */
	private function isPathAllowed($user, $path) {
		$folders = explode('/', $path);
		if ($folders[1] === $user && $folders[2] === 'files') {
			return $folders;
		}

		return false;
	}

	/**
	 * Creates a new thumbnail if it's missing
	 *
	 * This creates both the preview of max size and the thumbnail shown in the Gallery's photowall
	 *
	 * @param string $user owner of the Files
	 * @param File $file
	 * @param string $path full path to the file from the data folder
	 * @param string $pathFromVirtualRoot relative path, without <user>/files/
	 * @param OutputInterface $output
	 */
	private function createThumbnailForSupportedMediaType(
		$user, File $file, $path, $pathFromVirtualRoot, OutputInterface $output
	) {
		try {
			$fileId = $file->getId();
			$this->printToConsole("Analysing <info>[ID: $fileId] $path</info>...", $output);
			$mediaType = $file->getMimeType();
			if (in_array($mediaType, $this->supportedMediaTypes)) {
				$this->imagesCounter++;
				$preview = new Preview($user, 'files', $pathFromVirtualRoot);
				$this->checkCacheAndCreateThumbnail(
					$fileId, $preview, $path, $output
				);
			} else {
				$this->printToConsole("There is no preview provider for that media type", $output);
			}
		} catch (\Exception $exception) {
			$output->writeln("<error>Problem accessing $path</error>");
		}
	}

	/**
	 * Creates a new thumbnail if it's missing
	 *
	 * This creates both the preview of max size and the thumbnail shown in the Gallery's photowall
	 *
	 * @param int $fileId
	 * @param Preview $preview
	 * @param string $path full path to the file from the data folder
	 * @param OutputInterface $output
	 *
	 * @internal param string $user owner of the Files
	 */
	private function checkCacheAndCreateThumbnail(
		$fileId, Preview $preview, $path, OutputInterface $output
	) {
		$isCached = $preview->isCached($fileId);
		if ($isCached && !$this->regenerate) {
			$this->printToConsole(
				"The system already has a preview for that file", $output
			);
		} else {
			if ($this->regenerate) {
				$this->printToConsole(
					"Forcing the regeneration of the preview...", $output
				);
				$preview->deleteAllPreviews();
			} else {
				$this->printToConsole(
					"The scanner has found a missing preview! Generating...", $output
				);
			}
			$this->CreateThumbnail($preview, $path, $output);
		}
	}

	/**
	 * Creates a new thumbnail if it's missing
	 *
	 * This creates both the preview of max size and the thumbnail shown in the Gallery's photowall
	 *
	 * @param Preview $preview
	 * @param string $path full path to the file from the data folder
	 * @param OutputInterface $output
	 *
	 * @internal param string $user owner of the Files
	 */
	private function CreateThumbnail(Preview $preview, $path, OutputInterface $output) {
		try {
			$preview->setMaxX($this->thumbnailWidth);
			$preview->setMaxY($this->thumbnailHeight);
			$preview->setKeepAspect(true);
			$preview->getPreview();
			if ($preview) {
				$this->operationCounter++;
				$this->printToConsole("Preview generated!", $output);
			} else {
				$this->trackFailedOperation(
					$path, "The system was unable to generate a preview", $output
				);
			}
		} catch (\Exception $exception) {
			$extra = $exception->getMessage();
			$this->trackFailedOperation(
				$path, "There was an unexpected error while trying to generate the preview. $extra",
				$output
			);
		}
	}

}
