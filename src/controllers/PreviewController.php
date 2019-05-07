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
 * @since 3.2
 */
class PreviewController extends Controller
{
    // Public Methods
    // =========================================================================

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
        $request = Craft::$app->getRequest();
        $elementType = $request->getRequiredBodyParam('elementType');
        $sourceId = $request->getRequiredBodyParam('sourceId');
        $siteId = $request->getRequiredBodyParam('siteId');
        $draftId = $request->getBodyParam('draftId');

        if ($draftId) {
            $this->requireAuthorization('previewDraft:' . $draftId);
        } else {
            $this->requireAuthorization('previewElement:' . $sourceId);
        }

        // Create a 24 hour token
        $route = [
            'preview/preview', [
                'elementType' => $elementType,
                'sourceId' => $sourceId,
                'siteId' => $siteId,
                'draftId' => $draftId,
            ]
        ];

        $expiryDate = (new \DateTime())->add(new \DateInterval('P1D'));
        $token = Craft::$app->getTokens()->createToken($route, null, $expiryDate);

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
     * @return Response
     * @throws BadRequestHttpException
     * @throws \Throwable
     */
    public function actionPreview(string $elementType, int $sourceId, int $siteId, int $draftId = null): Response
    {
        // Make sure a token was used to get here
        $this->requireToken();

        /** @var ElementInterface $elementType */
        $query = $elementType::find()
            ->siteId($siteId)
            ->anyStatus();

        if ($draftId) {
            $query->draftId($draftId);
        } else {
            $query->id($sourceId);
        }

        $element = $query->one();

        if ($element) {
            Craft::$app->getElements()->setPlaceholderElement($element);
        }

        // Clear out the request token and re-route the request
        $request = Craft::$app->getRequest();
        $request->setToken(null);
        return Craft::$app->handleRequest($request, true);
    }
}
