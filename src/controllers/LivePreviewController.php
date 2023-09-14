<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craft\web\Controller;
use yii\base\InvalidRouteException;
use yii\console\Exception;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * Class LivePreviewController
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 * @deprecated in 3.2.0
 */
class LivePreviewController extends Controller
{
    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = ['preview'];

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // Mark this as a Live Preview request
        if ($action->id === 'preview') {
            $this->request->setIsLivePreview(true);
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
        $action = $this->request->getValidatedBodyParam('previewAction');

        if (!$action) {
            throw new BadRequestHttpException('Request missing required body param');
        }

        // Create the token
        $token = Craft::$app->getTokens()->createPreviewToken([
            'live-preview/preview', [
                'previewAction' => $action,
                'userId' => Craft::$app->getUser()->getId(),
            ],
        ]);

        if (!$token) {
            throw new ServerErrorHttpException('Could not create a Live Preview token.');
        }

        return $this->asJson(compact('token'));
    }

    /**
     * Renders a page for Live Preview.
     *
     * @param string $previewAction
     * @param int $userId
     * @return mixed
     * @throws BadRequestHttpException
     * @throws InvalidRouteException
     * @throws ServerErrorHttpException
     * @throws Exception
     */
    public function actionPreview(string $previewAction, int $userId): mixed
    {
        $this->requireToken();

        // Switch the identity for this one request
        /** @var User|null $user */
        $user = User::find()
            ->id($userId)
            ->status(UserQuery::STATUS_CREDENTIALED)
            ->one();

        if (!$user) {
            throw new ServerErrorHttpException('No user exists with an ID of ' . $userId);
        }

        Craft::$app->getUser()->setIdentity($user);

        // Add CORS headers
        $this->response->getHeaders()
            ->setDefault('Access-Control-Allow-Origin', '*')
            ->setDefault('Access-Control-Allow-Credentials', 'true')
            ->setDefault('Access-Control-Allow-Headers', 'X-Craft-Token');

        if ($this->request->getIsOptions()) {
            // This is just a preflight request, no need to route to the real controller action yet.
            return '';
        }

        return Craft::$app->runAction($previewAction);
    }
}
