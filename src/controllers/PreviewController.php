<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\ElementInterface;
use craft\web\Application;
use craft\web\Controller;
use Exception;
use Throwable;
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
    protected array|bool|int $allowAnonymous = [
        'preview' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
    ];

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
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
     * @throws Exception
     * @return Response
     */
    public function actionCreateToken(): Response
    {
        $elementType = $this->request->getRequiredParam('elementType');
        $canonicalId = $this->request->getParam('canonicalId') ?? $this->request->getRequiredBodyParam('sourceId');
        $siteId = $this->request->getRequiredParam('siteId');
        $draftId = $this->request->getParam('draftId');
        $revisionId = $this->request->getParam('revisionId');
        $token = $this->request->getParam('previewToken');
        $redirect = $this->request->getParam('redirect');

        if ($draftId) {
            $this->requireAuthorization('previewDraft:' . $draftId);
        } elseif ($revisionId) {
            $this->requireAuthorization('previewRevision:' . $revisionId);
        } else {
            $this->requireAuthorization('previewElement:' . $canonicalId);
        }

        // Create the token
        $token = Craft::$app->getTokens()->createPreviewToken([
            'preview/preview', [
                'elementType' => $elementType,
                'canonicalId' => (int)$canonicalId,
                'siteId' => (int)$siteId,
                'draftId' => (int)$draftId ?: null,
                'revisionId' => (int)$revisionId ?: null,
                'userId' => Craft::$app->getUser()->getId(),
            ],
        ], null, $token);

        if (!$token) {
            throw new ServerErrorHttpException(Craft::t('app', 'Could not create a preview token.'));
        }

        if ($redirect) {
            return $this->redirect($redirect);
        }

        return $this->asJson(compact('token'));
    }

    /**
     * Substitutes an element for the element being previewed for the remainder of the request, and reroutes the request.
     *
     * @param string $elementType
     * @phpstan-param class-string<ElementInterface> $elementType
     * @param int $canonicalId
     * @param int $siteId
     * @param int|null $draftId
     * @param int|null $revisionId
     * @param int|null $userId
     * @return Response
     * @throws BadRequestHttpException
     * @throws Throwable
     */
    public function actionPreview(
        string $elementType,
        int $canonicalId,
        int $siteId,
        ?int $draftId = null,
        ?int $revisionId = null,
        ?int $userId = null,
    ): Response {
        // Make sure a token was used to get here
        $this->requireToken();

        /** @var ElementInterface $elementType */
        $query = $elementType::find()
            ->siteId($siteId)
            ->status(null);

        if ($draftId) {
            $element = $query
                ->draftId($draftId)
                ->one();
        } elseif ($revisionId) {
            $element = $query
                ->revisionId($revisionId)
                ->one();
        } else {
            if ($userId) {
                // First check if there's a provisional draft
                $element = (clone $query)
                    ->draftOf($canonicalId)
                    ->provisionalDrafts()
                    ->draftCreator($userId)
                    ->one();
            }

            if (!isset($element)) {
                $element = $query
                    ->id($canonicalId)
                    ->one();
            }
        }

        if ($element) {
            if (!$element->lft && $element->getIsDerivative()) {
                // See if we can add structure data to it
                $canonical = $element->getCanonical(true);
                $element->structureId = $canonical->structureId;
                $element->root = $canonical->root;
                $element->lft = $canonical->lft;
                $element->rgt = $canonical->rgt;
                $element->level = $canonical->level;
            }

            $element->previewing = true;
            Craft::$app->getElements()->setPlaceholderElement($element);
        }

        // Prevent the browser from caching the response
        $this->response->setNoCacheHeaders();

        // Recheck whether this is an action request, this time ignoring the token
        $this->request->checkIfActionRequest(true, false);

        // Re-route the request, this time ignoring the token
        /** @var Application $app */
        $app = Craft::$app;
        $urlManager = $app->getUrlManager();
        $urlManager->checkToken = false;
        $urlManager->setRouteParams([], false);
        $urlManager->setMatchedElement(null);
        return $app->handleRequest($this->request, true);
    }
}
