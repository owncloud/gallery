<?php
/**
 * ownCloud - gallery
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Olivier Paroz <owncloud@interfasys.ch>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * @copyright Olivier Paroz 2015
 * @copyright Bernhard Posselt 2015
 */

use OCP\AppFramework\IAppContainer;

if (\OC::$server->getConfig()
				->getSystemValue('installed', false)
) {
	$galleryApp = new OCA\Gallery\AppInfo\Application();
	$galleryContainer = $galleryApp->getContainer();
	$galleryContainer->registerService(
		'userFolder', function (IAppContainer $c) {
		return null;
	}
	);
	$galleryContainer->registerService(
		'OCP\Encryption\IManager', function (IAppContainer $c) {
		return $c->getServer()
				 ->getEncryptionManager();
	}
	);
	$galleryContainer->registerService(
		'OCP\Files\Mount\IMountManager', function (IAppContainer $c) {
		return $c->getServer()
				 ->getMountManager();
	}
	);
	$galleryContainer->registerService(
		'OCP\Lock\ILockingProvider', function (IAppContainer $c) {
		return $c->getServer()
				 ->getLockingProvider();
	}
	);

	$createThumbnailsCmd = $galleryContainer->query('OCA\Gallery\Command\CreateThumbnails');
	$deleteThumbnailsCmd = $galleryContainer->query('OCA\Gallery\Command\DeleteThumbnails');

	$application->add($createThumbnailsCmd);
	$application->add($deleteThumbnailsCmd);
}
