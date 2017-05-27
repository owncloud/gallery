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
use Symfony\Component\Console\Helper\Table;

use OC\Files\Storage\Storage;

use OCP\IUser;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Mount\IMountPoint;

/**
 * Class DeleteThumbnails
 *
 * Deletes thumbnails of visual media types stored in the system
 *
 * @package OCA\Gallery\Command
 */
class DeleteThumbnails extends Thumbnails {

	const THUMBNAILS_FOLDER = 'thumbnails';

	/**
	 * Configures the command and describes parameters
	 */
	protected function configure() {
		$this->setName('gallery:delete-thumbnails')
			 ->setDescription('deletes thumbnails of supported visual media files')
			 ->addArgument(
				 'user_id',
				 InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
				 'will delete all thumbnails of visual media files for the given user(s)'
			 )
			 ->addOption(
				 'path',
				 'p',
				 InputArgument::OPTIONAL,
				 'limit scope to this path, eg. --path="/alice/files/Holidays", the user_id is determined by the path and the user_id parameter and --all are ignored'
			 )
			 ->addOption(
				 'quiet',
				 'q',
				 InputOption::VALUE_NONE,
				 'suppress output'
			 )
			 ->addOption(
				 'cache',
				 'c',
				 InputOption::VALUE_NONE,
				 'clear cache only'
			 )
			 ->addOption(
				 'all',
				 null,
				 InputOption::VALUE_NONE,
				 'will delete all thumbnails of visual media files'
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
		$this->initTools();

		$userInputErrorMsg =
			"Please specify a user id, \"--all\" to delete all thumbnails or \"--path=...\"";
		$pathInputErrorMsg =
			"The path given in \"--path=...\" does not start with \"/user_id/files\" or does not exist";
		try {
			list($inputPath, $users, $clearCacheOnly) =
				$this->parseParameters($input, $userInputErrorMsg, $pathInputErrorMsg, $output);
			$this->confirmAction($users, $inputPath, $input, $output);
		} catch (CommandException $exception) {
			$message = $exception->getMessage();
			$output->writeln("<error>$message</error>");

			return;
		}

		$this->deleteThumbnailsForUsersOrPath($users, $inputPath, $clearCacheOnly, $output);

		$this->presentResults($output);
	}

	/**
	 * @inherit
	 */
	protected function parseParameters(
		InputInterface $input, $userInputErrorMsg, $pathInputErrorMsg, OutputInterface $output
	) {
		$params = parent::parseParameters($input, $userInputErrorMsg, $pathInputErrorMsg, $output);

		return array_merge($params, [$input->getOption('cache')]);
	}

	/**
	 * @inherit
	 */
	protected function presentResults(OutputInterface $output) {
		parent::presentResults($output);

		if ($this->imagesCounter > 0) {
			$niceDate = $this->formatExecTime();
			$size = $this->formatSize($this->folderSize);
			$headers = [
				'Images found', 'Deleted thumbnails', 'Failed deletion', 'Elapsed time',
				'Space saved'
			];
			$rows = [
				$this->imagesCounter,
				$this->operationCounter,
				$this->failedOperationCounter,
				$niceDate,
				$size
			];

			$this->showSummary($headers, $rows, $output);

			$headers = ['List of failed deletions'];
			$this->showFailed($headers, $output);
		}

	}

	/**
	 * Scans a given user's filesystem and return all Ids of images
	 *
	 * @param Storage $storage
	 * @param IMountPoint $mount
	 * @param string $path
	 * @param string $user
	 * @param OutputInterface $output
	 *
	 * @return array
	 * @throws NotFoundCommandException
	 * @see OC\Files\Utils\Scanner::scan
	 */
	protected function getAllFileIds(
		$storage, IMountPoint $mount, $path, $user, OutputInterface $output
	) {
		/** @type Folder $thumbnailsFolder */
		$thumbnailsFolder = $this->getNode($path);
		if (!$thumbnailsFolder) {
			throw new NotFoundCommandException("The path provided is invalid");
		}
		foreach ($this->supportedMediaTypes as $mediaType) {
			/** @type File[] $images */
			// Does not work properly with external shares
			// https://github.com/owncloud/core/issues/19544
			$images = $thumbnailsFolder->searchByMime($mediaType);
			if ($images) {
				$output->writeln("<info>Found images of media type $mediaType</info>");
				$this->findAndDeleteThumbnails($user, $images, $output);
			}
		}
	}

	/**
	 * Makes sure the users really understand what is going to happen
	 *
	 * @param IUser[]|string[] $users
	 * @param string $inputPath
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws InputCommandException
	 */
	private function confirmAction(
		$users, $inputPath, InputInterface $input, OutputInterface $output
	) {
		if ($inputPath) {
			$this->getConfirmation(
				$input,
				"<question>Are you sure you want to delete the thumbnails for files in this path: $inputPath?</question>",
				$output
			);
		} else if ($users === $this->userManager->search('')) {
			$this->getConfirmation(
				$input,
				'<question>Are you sure you want to delete the thumbnails for all users?</question>',
				$output
			);
		} else {
			$userList = implode(',', $users);
			$this->getConfirmation(
				$input,
				"<question>Are you sure you want to delete the thumbnails for $userList?</question>",
				$output
			);
		}
	}

	/**
	 * Deletes thumbnails for all given users or for files belonging to a specific path
	 *
	 * @param IUser[]|string[] $users
	 * @param string $inputPath
	 * @param OutputInterface $output
	 */
	private function deleteThumbnailsForUsersOrPath(
		$users, $inputPath, $clearCacheOnly, OutputInterface $output
	) {
		foreach ($users as $user) {
			if (is_object($user)) {
				$user = $user->getUID();
			}
			if ($this->userManager->userExists($user)) {
				if ($inputPath) {
					$this->deleteThumbnailsForPath($user, $inputPath, $output);
				} else {
					$this->deleteThumbnailsForUser($user, $clearCacheOnly, $output);
				}
			} else {
				$output->writeln("<error>Unknown user $user</error>");
			}
		}
	}

	/**
	 * Deletes all thumbnails linked to files belonging to a specific path
	 *
	 * @param string $user
	 * @param string $inputPath
	 * @param OutputInterface $output
	 */
	private function deleteThumbnailsForPath($user, $inputPath, OutputInterface $output) {
		try {
			$this->performOperation('getAllFileIds', $user, $inputPath, $output);
		} catch (CommandException $exception) {
			$message = $exception->getMessage();
			$output->writeln("<error>$message</error>");
		}
	}

	/**
	 * Deletes all thumbnails for a user
	 *
	 * @param string $user
	 * @param bool $clearCacheOnly
	 * @param OutputInterface $output
	 */
	private function deleteThumbnailsForUser($user, $clearCacheOnly, OutputInterface $output) {
		try {
			// Has to be called before any filesystem or caching operation
			\OC_Util::tearDownFS();
			\OC_Util::setupFS($user);

			if ($clearCacheOnly) {
				$this->clearCacheForThumbnails($user, $output);
			} else {
				$this->deleteThumbnailsFolder($user, $output);
			}
		} catch (\OCP\Files\NotFoundException $exception) {
			$extra = $exception->getMessage();
			$output->writeln(
				"<error>Cannot delete the thumbnails folder for $user. $extra</error>"
			);
			$output->writeln(
				"Make sure you're running the scan command only as the user the web server runs as"
			);
		}
	}

	/**
	 * Deletes all thumbnails for a user
	 *
	 * @param string $user
	 * @param OutputInterface $output
	 */
	private function clearCacheForThumbnails($user, OutputInterface $output) {
		$output->writeln("<info>Removing all thumbnails for $user, from the cache</info>");

		$mount = $this->rootFolder->get('/' . $user);
		/** @type Storage $storage */
		$storage = $mount->getStorage();
		$cache = $storage->getCache('');
		$cache->remove(self::THUMBNAILS_FOLDER);
	}

	/**
	 * Deletes all thumbnails linked to a media file
	 *
	 * @param string $user owner of the Files
	 * @param OutputInterface $output
	 *
	 * @internal param \OCP\Files\File[] $images
	 */
	private function deleteThumbnailsFolder($user, OutputInterface $output) {
		$relativePath = self::THUMBNAILS_FOLDER;
		$successMessage = "<info>Deleted all thumbnails for $user</info>";
		$errorMessage = "<info>No thumbnails found for $user</info>";
		list($result, $imagesCount) =
			$this->deleteThumbnails(
				$user, $relativePath, $successMessage, $errorMessage, $output
			);
		if ($result) {
			$this->imagesCounter = $imagesCount;
			$this->operationCounter = $imagesCount;
		}
	}

	/**
	 * Finds and deletes all thumbnails linked to a media file
	 *
	 * @param string $user owner of the Files
	 * @param File[] $images
	 * @param OutputInterface $output
	 */
	private function findAndDeleteThumbnails($user, $images, OutputInterface $output) {
		$fullPath = 'Unknown';
		foreach ($images as $image) {
			$cancelled = $this->hasBeenInterrupted();
			if (!$cancelled) {
				try {
					$this->imagesCounter++;
					$fileId = $image->getId();
					$fullPath = $image->getPath();
					$this->lastFile = $fullPath;
					$this->deleteThumbnailsForFile($user, $fileId, $fullPath, $output);
				} catch (\Exception $exception) {
					$this->trackFailedOperation(
						$fullPath,
						'There was an unexpected error while trying to delete the thumbnails',
						$output
					);
				}
			}
		}
	}

	/**
	 * Delete the thumbnails for a specific file
	 *
	 * @param string $user owner of the Files
	 * @param int $fileId
	 * @param string $fullPath
	 * @param OutputInterface $output
	 */
	private function deleteThumbnailsForFile($user, $fileId, $fullPath, OutputInterface $output) {
		$relativePath = self::THUMBNAILS_FOLDER . '/' . $fileId;
		$successMessage = "Thumbnails for <info>[ID: $fileId] $fullPath</info> deleted!";
		$errorMessage = "No thumbnails found for <info>[ID: $fileId] $fullPath</info>";
		list($result) =
			$this->deleteThumbnails(
				$user, $relativePath, $successMessage, $errorMessage, $output
			);
		if ($result) {
			$this->operationCounter++;
		}
	}

	/**
	 * Delete a  thumbnails folder
	 *
	 * Could be the folder linked to a single ID or the whole thumbnails folder
	 *
	 * @param string $user owner of the Files
	 * @param string $relativePath
	 * @param string $successMessage
	 * @param string $errorMessage
	 * @param OutputInterface $output
	 *
	 * @return array <bool,int>
	 */
	private function deleteThumbnails(
		$user, $relativePath, $successMessage, $errorMessage, OutputInterface $output
	) {
		$success = false;
		$imagesCount = 0;
		/** @type Folder $ThumbnailsFolder */
		$ThumbnailsFolder = $this->getNode($relativePath, $user);
		if ($ThumbnailsFolder) {
			$this->folderSize += $ThumbnailsFolder->getSize();
			$imagesCount = count($ThumbnailsFolder->getDirectoryListing());
			$ThumbnailsFolder->delete();
			$this->printToConsole($successMessage, $output);
			$success = true;
		} else {
			$this->printToConsole($errorMessage, $output);
		}

		return [$success, $imagesCount];
	}

}
