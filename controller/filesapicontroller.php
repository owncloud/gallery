<?php
/**
 * Gallery
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Olivier Paroz <galleryapps@oparoz.com>
 *
 * @copyright Olivier Paroz 2014-2016
 */

namespace OCA\Gallery\Controller;

use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\ILogger;

use OCP\AppFramework\ApiController;
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
 * Class FilesApiController
 *
 * @package OCA\Gallery\Controller
 */
class FilesApiController extends ApiController {
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
	 * @NoCSRFRequired
	 * @CORS
	 *
	 * Returns a list of all media files available to the authenticated user
	 *
	 * @see FilesController::getList()
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
	 * @NoCSRFRequired
	 * @CORS
	 *
	 * Sends the file matching the fileId
	 *
	 * In case of error we send an HTML error page
	 * We need to keep the session open in order to be able to send the error message to the error
	 *     page
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
				$this->appName . '.page.error_page', ['code' => $code]
			);

			// Don't set a cookie for the error message, we don't want it in the API
			return new RedirectResponse($url);
		}

		// That's the only exception out of all the image media types
		if ($download['mimetype'] === 'image/svg+xml') {
			$download['mimetype'] = 'text/plain';
		}

		return new ImageResponse($download);
	}
}
