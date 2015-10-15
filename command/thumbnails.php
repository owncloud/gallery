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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ConfirmationQuestion;

// Static methods needed are not available in OCP
use OC\Files\Filesystem;

use OCP\IUserManager;
use OCP\IUser;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Node;

use OCA\Gallery\Service\ConfigService;

/**
 * Class Thumbnails
 *
 * Manages thumbnails of visual media types supported by the system
 *
 * @package OCA\Gallery\Command
 */
abstract class Thumbnails extends Command {

	/** @var IUserManager */
	protected $userManager;
	/** @var IRootFolder */
	protected $rootFolder;
	/** @var IMountManager */
	protected $mountManager;
	/** @var ConfigService */
	protected $configService;
	/** @var array */
	protected $supportedMediaTypes;
	/** @var float */
	protected $execTime = 0;
	/** @var int */
	protected $folderSize = 0;
	/** @var int */
	protected $foldersCounter = 0;
	/** @var int */
	protected $filesCounter = 0;
	/** @var int */
	protected $imagesCounter = 0;
	/** @var string */
	protected $lastFile = '';
	/** @var string */
	protected $lastFolder = '';
	/** @var int */
	protected $operationCounter = 0;
	/** @var int */
	protected $failedOperationCounter = 0;
	/** @var array */
	protected $failedOperations;
	/** @var bool */
	protected $interrupted = false;

	/**
	 * Constructor
	 *
	 * @param IUserManager $userManager
	 * @param IRootFolder $rootFolder
	 * @param IMountManager $mountManager
	 * @param ConfigService $configService
	 */
	public function __construct(
		IUserManager $userManager,
		IRootFolder $rootFolder,
		IMountManager $mountManager,
		ConfigService $configService
	) {
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		// IMountManager is 8.2+ only
		$this->mountManager = $mountManager;
		$this->configService = $configService;
		// We want all the media types supported via providers only
		$this->supportedMediaTypes = $this->configService->getSupportedMediaTypes(true, false);

		parent::__construct();
	}

	/**
	 * Processes PHP errors as exceptions in order to be able to keep track of problems
	 *
	 * @see https://secure.php.net/manual/en/function.set-error-handler.php
	 *
	 * @param int $severity the level of the error raised
	 * @param string $message
	 * @param string $file the filename that the error was raised in
	 * @param int $line the line number the error was raised
	 *
	 * @throws \ErrorException
	 */
	public function exceptionErrorHandler($severity, $message, $file, $line) {
		if (!(error_reporting() & $severity)) {
			// This error code is not included in error_reporting
			return;
		}
		throw new \ErrorException($message, 0, $severity, $file, $line);
	}

	/**
	 * Initialises some useful tools for the Command
	 */
	protected function initTools() {
		// Start the timer
		$this->execTime = -microtime(true);

		// Convert PHP errors to exceptions
		set_error_handler([$this, 'exceptionErrorHandler'], E_ALL);

		// Collect interrupts and notify the running command
		pcntl_signal(SIGTERM, [$this, 'cancelOperation']);
		pcntl_signal(SIGINT, [$this, 'cancelOperation']);
	}

	/**
	 * Parses all the parameters submitted by the command user
	 *
	 * @param InputInterface $input
	 * @param string $userInputErrorMsg
	 * @param string $pathInputErrorMsg
	 * @param OutputInterface $output
	 *
	 * @return array
	 * @throws InputCommandException
	 */
	protected function parseParameters(
		InputInterface $input, $userInputErrorMsg, $pathInputErrorMsg, OutputInterface $output
	) {
		$inputPath = $input->getOption('path');
		if ($inputPath) {
			$inputPath = '/' . trim($inputPath, '/');
			try {
				list (, $user, $topFolder) = explode('/', $inputPath, 4);
				$users = [$user];
			} catch (\ErrorException $exception) {
				throw new InputCommandException($pathInputErrorMsg);
			}
			$this->checkPathParameter($topFolder, $pathInputErrorMsg);
		} else if ($input->getOption('all')) {
			$users = $this->userManager->search('');
		} else {
			$users = $input->getArgument('user_id');
		}
		$this->checkUserParameter($users, $userInputErrorMsg);

		return [$inputPath, $users];
	}

	/**
	 * Presents the results of the operation in tabular form
	 *
	 * @param OutputInterface $output
	 */
	protected function presentResults(OutputInterface $output) {
		// Stop the timer
		$this->execTime += microtime(true);

		$output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
		$output->writeln("");
	}

	/**
	 * Formats microtime into a human readable format
	 *
	 * @return string
	 */
	protected function formatExecTime() {
		list($secs, $tens) = explode('.', sprintf("%.1f", ($this->execTime)));
		$niceDate = date('H:i:s', $secs) . '.' . $tens;

		return $niceDate;
	}

	/**
	 * Formats bytes into a human readable format
	 *
	 * @param int $bytes
	 * @param int $decimals
	 *
	 * @return string
	 * @see https://secure.php.net/manual/en/function.filesize.php#106569
	 */
	protected function formatSize($bytes, $decimals = 2) {
		$size = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);

		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
	}

	/**
	 * Shows a summary of operations
	 *
	 * @param string[] $headers
	 * @param string[] $rows
	 * @param OutputInterface $output
	 */
	protected function showSummary($headers, $rows, OutputInterface $output) {
		$niceDate = $this->formatExecTime();
		$size = $this->formatSize($this->folderSize);
		if (!$rows) {
			$rows = [
				$this->foldersCounter,
				$this->filesCounter,
				$this->imagesCounter,
				$this->operationCounter,
				$this->failedOperationCounter,
				$niceDate,
				$size
			];
		}
		$table = new Table($output);
		$table
			->setHeaders($headers)
			->setRows([$rows]);
		$table->render();

		$output->writeln("Last scanned file <info>$this->lastFile</info>");
	}

	/**
	 * Shows details about failed operations
	 *
	 * @param string[] $headers
	 * @param OutputInterface $output
	 */
	protected function showFailed($headers, OutputInterface $output) {
		if (count($this->failedOperations)) {
			$table = new Table($output);
			$table->setHeaders($headers);
			foreach ($this->failedOperations as $failedOperation) {
				$table->addRow([$failedOperation]);
			}
			$table->render();
		}
	}

	/**
	 * Performs a given operation on a given user's file system
	 *
	 * @param string $scanOperation
	 * @param string $user
	 * @param string $dir
	 * @param OutputInterface $output
	 *
	 * @return array
	 * @throws ForbiddenCommandException|InputCommandException
	 * @see OC\Files\Utils\Scanner::scan
	 */
	protected function performOperation($scanOperation, $user, $dir, OutputInterface $output
	) {
		if (!Filesystem::isValidPath($dir)) {
			throw new InputCommandException('Invalid path to scan');
		}
		$mounts = $this->getMounts($dir, $user);
		$result = [];
		foreach ($mounts as $mount) {
			$previewsAllowed = $mount->getOption('previews', true);
			if (is_null($mount->getStorage()) || !$previewsAllowed) {
				continue;
			}
			$storage = $mount->getStorage();
			// if the home storage isn't writable then the scanner is run as the wrong user
			if ($storage->instanceOfStorage('\OCP\Files\IHomeStorage') and
				(!$storage->isCreatable('') or !$storage->isCreatable('files'))
			) {
				throw new ForbiddenCommandException(
					"<error>Home storage for user $user not writable</error>\nMake sure you're running the scan command only as the user the web server runs as"
				);
			}
			// We can now execute the wanted operation
			$result[] = call_user_func_array(
				[$this, $scanOperation],
				[$storage, $mount, $dir, $user, $output]
			);
		}

		return $result;
	}

	/**
	 * Determines if we have a file
	 *
	 * @param Node $node
	 *
	 * @return bool
	 */
	protected function isFile(Node $node) {
		$isFile = false;
		// TODO: Find a more reliable way as we can't trust FileInfo
		if ($node->getType() === 'file') {
			$isFile = true;
		}

		return $isFile;
	}

	/**
	 * Makes sure the folder or file exists in the user's filesystem
	 *
	 * @param string $path a full path or a relative path starting from the user's folder
	 * @param string|null $user
	 *
	 * @return bool|Node
	 *
	 */
	protected function getNode($path, $user = null) {
		if ($user) {
			$path = '/' . $user . '/' . $path;
		}
		if ($this->rootFolder->nodeExists($path)) {
			return $this->rootFolder->get($path);
		}

		return false;
	}

	/**
	 * @return bool
	 */
	protected function hasBeenInterrupted() {
		$cancelled = false;
		pcntl_signal_dispatch();
		if ($this->interrupted) {
			$cancelled = true;
		}

		return $cancelled;
	}

	/**
	 * Asks for confirmation before proceeding
	 *
	 * @param InputInterface $input
	 * @param string $question
	 * @param OutputInterface $output
	 *
	 * @throws InputCommandException
	 */
	protected function getConfirmation(InputInterface $input, $question, OutputInterface $output) {
		$helper = $this->getHelper('question');
		$confirmationQuestion = new ConfirmationQuestion($question, false);

		$quiet = false;
		if ($output->getVerbosity() === 0) {
			$quiet = true;
			$output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
		}
		if (!$helper->ask($input, $output, $confirmationQuestion)) {
			throw new InputCommandException("Operation aborted");
		}
		if ($quiet) {
			$output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
		}
	}

	/**
	 * Tracks failed operations
	 *
	 * @param string $path
	 * @param string $message
	 * @param OutputInterface $output
	 */
	protected function trackFailedOperation($path, $message, OutputInterface $output) {
		$this->failedOperationCounter++;
		$this->failedOperations[] = $path;
		$this->printToConsole("<error>$message</error>", $output);
	}

	/**
	 * Prints a message to the console if we're not in quiet mode
	 *
	 * @param string $message
	 * @param OutputInterface $output
	 */
	protected function printToConsole($message, OutputInterface $output) {
		if (!$output->getVerbosity() === 0) {
			$output->writeln($message);
		}
	}

	/**
	 * Changes the status of the command to "interrupted"
	 *
	 * Gives a chance to the command to properly terminate what it's doing
	 */
	private function cancelOperation() {
		$this->interrupted = true;
	}

	/**
	 * Makes sure the user has entered a valid path
	 *
	 * @param IUser[]|string[] $users
	 * @param string $userInputErrorMsg
	 *
	 * @throws InputCommandException
	 */
	private function checkUserParameter($users, $userInputErrorMsg) {
		if (count($users) === 0) {
			throw new InputCommandException($userInputErrorMsg);
		}
	}

	/**
	 * Makes sure the user has entered a valid path
	 *
	 * It's too early to make sure the path is valid as we'll only mount the filesystem later in
	 * the process
	 *
	 * @param string $pathInputErrorMsg
	 *
	 * @throws InputCommandException
	 */
	private function checkPathParameter($topFolder, $pathInputErrorMsg) {
		if ($topFolder !== 'files') {
			throw new InputCommandException($pathInputErrorMsg);
		}
	}

	/**
	 * Retrieves all storage mount points in $dir, including external ones
	 *
	 * @param string $dir
	 * @param string $user
	 *
	 * @return IMountPoint[]
	 * @throws InputCommandException
	 * @see OC\Files\Utils\Scanner::getMounts
	 */
	private function getMounts($dir, $user) {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($user);

		$testPath = $this->rootFolder->nodeExists($dir);
		if ($testPath) {
			$mounts = $this->mountManager->findIn($dir);
			$mounts[] = $this->mountManager->find($dir);
			$mounts = array_reverse($mounts); //start with the mount of $dir

			return $mounts;
		} else {
			throw new InputCommandException("The path provided does not exist");
		}
	}

}
