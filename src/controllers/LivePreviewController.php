<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\elements\User;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * Class LivePreviewController
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1
 */
class LivePreviewController extends Controller
{
    /**
     * @inheritdoc
     */
    protected $allowAnonymous = ['preview'];

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        // Mark this as a Live Preview request
        if ($action->id === 'preview') {
            Craft::$app->getRequest()->setIsLivePreview(true);
        }

        return parent::beforeAction($action);
    }

    /**
     * Creates a token for Live Preview requests.
     *
     * @throws ServerErrorHttpException if the token couldn't be created
     * @throws BadRequestHttpException
     * @throws \Exception
     * @return Response
     */
    public function actionCreateToken(): Response
    {
        $action = Craft::$app->getRequest()->getValidatedBodyParam('previewAction');

        if (!$action) {
            throw new BadRequestHttpException('Request missing required body param');
        }

        // Create a 24 hour token
        $route = [
            'live-preview/preview', [
                'previewAction' => $action,
                'userId' => Craft::$app->getUser()->getId(),
            ]
        ];

        $expiryDate = (new \DateTime())->add(new \DateInterval('P1D'));
        $token = Craft::$app->getTokens()->createToken($route, null, $expiryDate);

        if (!$token) {
            throw new ServerErrorHttpException(Craft::t('app', 'Could not create a Live Preview token.'));
        }

        return $this->asJson(compact('token'));
    }

    /**
     * Renders a page for Live Preview.
     *
     * @param string $previewAction
     * @param int $userId
     * @return mixed
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\base\InvalidRouteException
     * @throws ServerErrorHttpException
     * @throws \yii\console\Exception
     */
    public function actionPreview(string $previewAction, int $userId)
    {
        $this->requireToken();

        // Switch the identity for this one request
        $user = User::findOne($userId);
        if (!$user) {
            throw new ServerErrorHttpException('No user exists with an ID of ' . $userId);
        }
        Craft::$app->getUser()->setIdentity($user);

        // Add CORS headers
        Craft::$app->getResponse()->getHeaders()
            ->add('Access-Control-Allow-Origin', Craft::$app->getRequest()->getOrigin())
            ->add('Access-Control-Allow-Credentials', 'true')
            ->add('Access-Control-Allow-Headers', 'X-Craft-Token');

        if (Craft::$app->getRequest()->getIsOptions()) {
            // This is just a preflight request, no need to route to the real controller action yet.
            return '1';
        }

        return Craft::$app->runAction($previewAction);
    }
}
