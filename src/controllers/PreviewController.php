<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\ElementInterface;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * Preview controller.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class PreviewController extends Controller
{
    /**
     * @inheritdoc
     */
    public $allowAnonymous = [
        'preview' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
    ];

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        // Don't require CSRF validation for POSTed preview requests
        if ($action->id === 'preview') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    /**
     * Creates a token for previewing/sharing an element.
     *
     * @throws ServerErrorHttpException if the token couldn't be created
     * @throws BadRequestHttpException
     * @throws \Exception
     * @return Response
     */
    public function actionCreateToken(): Response
    {
        $elementType = $this->request->getRequiredBodyParam('elementType');
        $sourceId = $this->request->getRequiredBodyParam('sourceId');
        $siteId = $this->request->getRequiredBodyParam('siteId');
        $draftId = $this->request->getBodyParam('draftId');
        $revisionId = $this->request->getBodyParam('revisionId');
        $provisional = (bool)($this->request->getBodyParam('provisional') ?? false);

        if ($draftId) {
            $this->requireAuthorization('previewDraft:' . $draftId);
        } else if ($revisionId) {
            $this->requireAuthorization('previewRevision:' . $revisionId);
        } else {
            $this->requireAuthorization('previewElement:' . $sourceId);
        }

        // Create the token
        $token = Craft::$app->getTokens()->createPreviewToken([
            'preview/preview', [
                'elementType' => $elementType,
                'sourceId' => (int)$sourceId,
                'siteId' => (int)$siteId,
                'draftId' => (int)$draftId ?: null,
                'revisionId' => (int)$revisionId ?: null,
                'provisional' => $provisional,
            ],
        ]);

        if (!$token) {
            throw new ServerErrorHttpException(Craft::t('app', 'Could not create a preview token.'));
        }

        return $this->asJson(compact('token'));
    }

    /**
     * Substitutes an element for the element being previewed for the remainder of the request, and reroutes the request.
     *
     * @param string $elementType
     * @param int $sourceId
     * @param int $siteId
     * @param int|null $draftId
     * @param int|null $revisionId
     * @param bool $provisional
     * @return Response
     * @throws BadRequestHttpException
     * @throws \Throwable
     */
    public function actionPreview(
        string $elementType,
        int $sourceId,
        int $siteId,
        ?int $draftId = null,
        ?int $revisionId = null,
        bool $provisional = false
    ): Response {
        // Make sure a token was used to get here
        $this->requireToken();

        /** @var ElementInterface $elementType */
        $query = $elementType::find()
            ->siteId($siteId)
            ->anyStatus();

        if ($draftId) {
            $query
                ->draftId($draftId)
                ->provisionalDrafts($provisional);
        } else if ($revisionId) {
            $query->revisionId($revisionId);
        } else {
            $query->id($sourceId);
        }

        $element = $query->one();

        if ($element) {
            $element->previewing = true;
            Craft::$app->getElements()->setPlaceholderElement($element);
        }

        // Prevent the browser from caching the response
        $this->response->setNoCacheHeaders();

        // Recheck whether this is an action request, this time ignoring the token
        $this->request->checkIfActionRequest(true, false);

        // Re-route the request, this time ignoring the token
        $urlManager = Craft::$app->getUrlManager();
        $urlManager->checkToken = false;
        $urlManager->setRouteParams([], false);
        $urlManager->setMatchedElement(null);
        return Craft::$app->handleRequest($this->request, true);
    }
}
