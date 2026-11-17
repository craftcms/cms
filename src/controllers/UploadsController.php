<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\filters\IpRateLimitIdentity;
use craft\helpers\UrlHelper;
use craft\uploads\AssetUploadHandler;
use craft\web\Controller;
use yii\filters\RateLimiter;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;

/**
 * Starts, receives, and completes asset uploads that go straight to storage when the filesystem allows it.
 *
 * See [[\craft\services\Uploads]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class UploadsController extends Controller
{
    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = [
        // Guests need an Uploads::EVENT_AUTHORIZE_UPLOAD handler's approval
        'start' => self::ALLOW_ANONYMOUS_LIVE,
        'complete' => self::ALLOW_ANONYMOUS_LIVE,
        'cancel' => self::ALLOW_ANONYMOUS_LIVE,
        // The token in the URL authorizes the request, and the upload already started
        'transfer' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
    ];

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return parent::behaviors() + [
            'rateLimiter' => [
                'class' => RateLimiter::class,
                'only' => ['start'],
                'enableRateLimitHeaders' => false,
                // Users are limited by permissions; guests by IP, at Craft 6's limit, since each file is one start
                'user' => fn() => Craft::$app->getUser()->getIsGuest() && Craft::$app->getConfig()->getGeneral()->defaultRateLimit
                    ? new IpRateLimitIdentity(['keyPrefix' => 'upload-start', 'limit' => 60, 'window' => 60])
                    : null,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // The token in the URL authorizes the request, and clients such as curl don't send CSRF tokens
        if ($action->id === 'transfer') {
            $this->enableCsrfValidation = false;
        }

        if (!parent::beforeAction($action)) {
            return false;
        }

        // Actions that allow guests skip the control panel's access check, which control panel requests still need
        if ($action->id !== 'transfer' && $this->request->getIsCpRequest()) {
            $this->requireLogin();
            $this->requirePermission('accessCp');
        }

        return true;
    }

    /**
     * Starts an asset upload.
     *
     * Send `filename`, `size`, and the destination parameters described by [[AssetUploadHandler]].
     */
    public function actionStart(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $params = $this->request->getBodyParams();
        unset($params[$this->request->csrfParam], $params['filename'], $params['size']);
        $filename = $this->request->getRequiredBodyParam('filename');

        if (!is_string($filename)) {
            throw new BadRequestHttpException('The filename must be a string.');
        }

        if (isset($params['folderId'], $params['fieldId'])) {
            throw new BadRequestHttpException('Send either folderId or fieldId, not both.');
        }

        $upload = Craft::$app->getUploads()->start(
            AssetUploadHandler::class,
            $filename,
            (int)$this->request->getRequiredBodyParam('size'),
            $params,
        );

        $this->response->setStatusCode(201);

        // The same shape as Craft 6's upload sessions
        return $this->asJson([
            'id' => $upload['id'],
            'transport' => [
                'type' => 'put',
                'options' => [
                    'url' => $upload['url'],
                    'method' => $upload['method'],
                    'headers' => (object)$upload['headers'],
                ],
            ],
            'urls' => [
                'complete' => UrlHelper::actionUrl('uploads/complete', ['id' => $upload['id']]),
                'cancel' => UrlHelper::actionUrl('uploads/cancel', ['id' => $upload['id']]),
            ],
            'expiresAt' => $upload['expiresAt'],
        ]);
    }

    /**
     * Receives a file for an upload whose filesystem can’t accept it directly. The token in the URL routes and
     * authorizes the request.
     */
    public function actionTransfer(string $uploadId): Response
    {
        try {
            $this->requireToken();

            if (!$this->request->getIsPut() && !$this->request->getIsPost()) {
                throw new MethodNotAllowedHttpException('Send the file as the body of a PUT request.');
            }

            $stream = fopen('php://input', 'rb');

            try {
                $size = Craft::$app->getUploads()->transfer($uploadId, $stream);
            } finally {
                fclose($stream);
            }
        } catch (HttpException $exception) {
            // Upload clients such as curl don't ask for JSON, so the error handler would render an HTML page
            $this->response->setStatusCode($exception->statusCode);
            return $this->asJson(['error' => $exception->getMessage()]);
        }

        return $this->asJson(['id' => $uploadId, 'size' => $size]);
    }

    /**
     * Completes an upload, creating or replacing the asset.
     */
    public function actionComplete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        return $this->asJson(Craft::$app->getUploads()->complete($this->request->getRequiredParam('id')));
    }

    /**
     * Cancels an upload and deletes its file.
     */
    public function actionCancel(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        Craft::$app->getUploads()->cancel($this->request->getRequiredParam('id'));

        return $this->asSuccess();
    }
}
