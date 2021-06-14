<?php
/**
 * Gallery
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Olivier Paroz <galleryapps@oparoz.com>
 *
 * @copyright Olivier Paroz 2016
 */

namespace OCA\Gallery\Controller;

use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\ILogger;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\RedirectResponse;

use OCA\Gallery\Http\ImageResponse;
use OCA\Gallery\Service\SearchFolderService;
use OCA\Gallery\Service\ConfigService;
use OCA\Gallery\Service\SearchMediaService;
use OCA\Gallery\Service\DownloadService;
use OCA\Gallery\Service\ServiceException;
use OCP\Share\IManager;

/**
 * Class FilesController
 *
 * @package OCA\Gallery\Controller
 */
class FilesController extends Controller {
	use Files;
	use HttpError;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IManager */
	private $shareManager;

	/**
	 * Constructor
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IURLGenerator $urlGenerator
	 * @param SearchFolderService $searchFolderService
	 * @param ConfigService $configService
	 * @param SearchMediaService $searchMediaService
	 * @param DownloadService $downloadService
	 * @param ILogger $logger
	 * @param IManager $shareManager
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IURLGenerator $urlGenerator,
		SearchFolderService $searchFolderService,
		ConfigService $configService,
		SearchMediaService $searchMediaService,
		DownloadService $downloadService,
		ILogger $logger,
		IManager $shareManager
	) {
		parent::__construct($appName, $request);

		$this->urlGenerator = $urlGenerator;
		$this->searchFolderService = $searchFolderService;
		$this->configService = $configService;
		$this->searchMediaService = $searchMediaService;
		$this->downloadService = $downloadService;
		$this->logger = $logger;
		$this->shareManager = $shareManager;
	}

	/**
	 * @NoAdminRequired
	 *
	 * Returns a list of all media files available to the authenticated user
	 *
	 *    * Authentication can be via a login/password or a token/(password)
	 *    * For private galleries, it returns all media files, with the full path from the root
	 *     folder For public galleries, the path starts from the folder the link gives access to
	 *     (virtual root)
	 *    * An exception is only caught in case something really wrong happens. As we don't test
	 *     files before including them in the list, we may return some bad apples
	 *
	 * @param string $location a path representing the current album in the app
	 * @param string $features the list of supported features
	 * @param string $etag the last known etag in the client
	 * @param string $mediatypes the list of supported media types
	 *
	 * @return array <string,array<string,string|int>>|Http\JSONResponse
	 */
	public function getList($location, $features, $etag, $mediatypes) {
		$featuresArray = \explode(';', $features);
		$mediaTypesArray = \explode(';', $mediatypes);

		$token = $this->request->getParam('token');
		if ($token) {
			$share = $this->shareManager->getShareByToken($token);

			// Prevent user to see directory content if share is a file drop
			if (($share->getPermissions() & \OCP\Constants::PERMISSION_READ) !== \OCP\Constants::PERMISSION_READ) {
				return $this->formatResults([], [], [], "", "");
			}
		}

		try {
			return $this->getFilesAndAlbums($location, $featuresArray, $etag, $mediaTypesArray);
		} catch (\Exception $exception) {
			return $this->jsonError($exception, $this->request, $this->logger);
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * Sends the file matching the fileId
	 *
	 * @param int $fileId the ID of the file we want to download
	 * @param string|null $filename
	 *
	 * @return ImageResponse
	 */
	public function download($fileId, $filename = null) {
		try {
			$download = $this->getDownload($fileId, $filename);
		} catch (ServiceException $exception) {
			$code = $this->getHttpStatusCode($exception);
			$url = $this->urlGenerator->linkToRoute(
				$this->appName . '.page.error_page',
				['code' => $code]
			);

			$response = new RedirectResponse($url);
			$response->addCookie('galleryErrorMessage', $exception->getMessage());

			return $response;
		}

		// That's the only exception out of all the image media types we serve
		if ($download['mimetype'] === 'image/svg+xml') {
			$download['mimetype'] = 'text/plain';
		}

		return new ImageResponse($download);
	}
}
