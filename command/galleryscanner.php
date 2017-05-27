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

use Symfony\Component\Console\Output\OutputInterface;

// Does not exist in OCP
use OC\Files\Cache\ChangePropagator;
// Does not exist in OCP
use OC\Files\Cache\Scanner;
// Static methods needed are not available in OCP
use OC\Files\Filesystem;
use OC\Files\Storage\Storage;
// 8.2+ only
use OC\Lock\DBLockingProvider;

use OCP\IDBConnection;
use OCP\Files\Mount\IMountPoint;
use OCP\Lock\ILockingProvider;

/**
 * Class GalleryScanner
 *
 * Scans a given path and calls back post scanning methods
 *
 * @package OCA\Gallery\Command
 */
class GalleryScanner {

	/** @var IDBConnection */
	private $database;
	/** @var ChangePropagator */
	private $propagator;
	/** @var ILockingProvider */
	private $lockingProvider;

	/**
	 * Constructor
	 *
	 * @param IDBConnection $database
	 * @param ChangePropagator $propagator
	 * @param ILockingProvider $lockingProvider
	 */
	public function __construct(
		IDBConnection $database,
		ChangePropagator $propagator,
		ILockingProvider $lockingProvider
	) {
		$this->database = $database;
		$this->propagator = $propagator;
		$this->lockingProvider = $lockingProvider;
	}

	/**
	 * Scans a given user's specific mounted storage and updates the cache
	 *
	 * @param Storage $storage the storage location to scan
	 * @param IMountPoint $mount
	 * @param string $path
	 * @param string $user
	 * @param Callable $fileCallback the method to call after a file has been cached
	 * @param Callable $folderCallback the method to call after a folder has been cached
	 * @param OutputInterface $output
	 *
	 * @return array
	 * @see OC\Files\Utils\Scanner::scan
	 */
	public function scan(
		Storage $storage,
		IMountPoint $mount,
		$path, $user,
		Callable $fileCallback,
		Callable $folderCallback,
		OutputInterface $output
	) {
		$relativePath = $mount->getInternalPath($path);
		$scanner = $storage->getScanner();
		$scanner->setUseTransactions(false);
		$this->attachListener(
			$mount, $user, $fileCallback, $folderCallback, $output
		);
		$isDbLocking = $this->lockingProvider instanceof DBLockingProvider;
		if (!$isDbLocking) {
			$this->database->beginTransaction();
		}
		$size = $scanner->scan(
			$relativePath,
			\OC\Files\Cache\Scanner::SCAN_RECURSIVE,
			\OC\Files\Cache\Scanner::REUSE_ETAG | \OC\Files\Cache\Scanner::REUSE_SIZE
		);
		if (!$isDbLocking) {
			$this->database->commit();
		}

		return $size;
	}

	/**
	 * Attaches the callbacks to the storage's scanner postScanFile and postScanFolder events
	 *
	 * @param IMountPoint $mount
	 * @param string $user
	 * @param Callable $fileCallback
	 * @param Callable $folderCallback
	 * @param OutputInterface $output
	 *
	 * @see OC\Files\Utils\Scanner::attachListener
	 */
	private function attachListener(
		IMountPoint $mount,
		$user,
		Callable $fileCallback,
		Callable $folderCallback,
		OutputInterface $output
	) {
		$scanner = $mount->getStorage()
						 ->getScanner();
		$scanner->listen(
			'\OC\Files\Cache\Scanner', 'postScanFile',
			function ($path) use ($fileCallback, $mount, $user, $output) {
				call_user_func_array(
					$fileCallback, [$path, $mount, $user, $output]
				);
			}
		);
		$scanner->listen(
			'\OC\Files\Cache\Scanner', 'postScanFolder',
			function ($path) use ($folderCallback, $mount, $user, $output) {
				call_user_func_array(
					$folderCallback, [$path, $mount, $user, $output]
				);
			}
		);
		$this->addChangesToPropagator($scanner, $mount);
	}

	/**
	 * Collects cache changes
	 *
	 * Listens to addToCache and removeFromCache events
	 *
	 * @param Scanner $scanner
	 * @param IMountPoint $mount
	 */
	private function addChangesToPropagator(Scanner $scanner, IMountPoint $mount) {
		// Propagate etag and mtimes when files are changed or removed
		$propagator = $this->propagator;
		$propagatorListener = function ($path) use ($mount, $propagator) {
			$fullPath = Filesystem::normalizePath($mount->getMountPoint() . $path);
			$propagator->addChange($fullPath);
		};
		$scanner->listen('\OC\Files\Cache\Scanner', 'addToCache', $propagatorListener);
		$scanner->listen('\OC\Files\Cache\Scanner', 'removeFromCache', $propagatorListener);
	}

}
