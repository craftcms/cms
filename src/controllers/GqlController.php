<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\errors\GqlException;
use craft\helpers\DateTimeHelper;
use craft\models\GqlToken;
use craft\web\assets\graphiql\GraphiQlAsset;
use craft\web\Controller;
use GraphQL\GraphQL;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

Craft::$app->requireEdition(Craft::Pro);

/**
 * The GqlController class is a controller that handles various GraphQL related tasks.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class GqlController extends Controller
{
    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;

    /**
     * @inheritdoc
     */
    public $allowAnonymous = ['index'];

    /**
     * Performs a GraphQL query.
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        $gqlService = Craft::$app->getGql();
        $request = Craft::$app->getRequest();

        $token = null;
        $authorizationHeader = Craft::$app->request->headers->get('authorization');

        if (preg_match('/^Bearer\s+(.+)$/i', $authorizationHeader, $matches)) {
            $accessToken = $matches[1];
            $token = $gqlService->getTokenByAccessToken($accessToken);
        }

        // What if something already set it on the service?
        if (!$token) {
            try {
                $token = $gqlService->getCurrentToken();
            } catch (GqlException $exception) {
                // Or not.
            }
        }

        $tokenExpired = $token && $token->expiryDate && $token->expiryDate->getTimestamp() <= DateTimeHelper::currentTimeStamp();

        if (!$token || !$token->enabled || $tokenExpired) {
            throw new ForbiddenHttpException('Invalid authorization token.');
        }

        if ($request->getIsPost() && $query = $request->post('query')) {
            $input = $query;
        } else if ($request->getIsGet() && $query = $request->get('query')) {
            $input = $query;
        } else {
            $data = $request->getRawBody();
            $data = json_decode($data, true);
            $input = @$data['query'];
        }

        if ($input) {
            $devMode = Craft::$app->getConfig()->getGeneral()->devMode;
            $schema = $gqlService->getSchema($token, $devMode, $input);

            $result = GraphQL::executeQuery($schema, $input, null, null, null)->toArray(true);
        } else {
            throw new BadRequestHttpException('Request missing required param');
        }

        return $this->asJson($result);
    }

    /**
     * @return Response
     * @throws ForbiddenHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionGraphiql(): Response
    {
        $this->requireAdmin();
        $this->getView()->registerAssetBundle(GraphiQlAsset::class);

        $tokens = [];

        foreach (Craft::$app->getGql()->getTokens() as $token) {
            $tokens[$token->name] = $token->accessToken;
        }

        return $this->renderTemplate('graphql/graphiql', [
            'url' => '/actions/gql',
            'tokens' => $tokens,
        ]);
    }

    /**
     * @return Response
     * @throws ForbiddenHttpException
     */
    public function actionViewTokens(): Response
    {
        $this->requireAdmin();
        return $this->renderTemplate('graphql/tokens/_index');
    }

    /**
     * @param int|null $tokenId
     * @param GqlToken|null $token
     * @return Response
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionEditToken(int $tokenId = null, GqlToken $token = null): Response
    {
        $this->requireAdmin();

        $gqlService = Craft::$app->getGql();

        if ($token || $tokenId) {
            if (!$token) {
                $token = $gqlService->getTokenById($tokenId);
            }

            if (!$token) {
                throw new NotFoundHttpException('Token not found');
            }

            $title = trim($token->name) ?: Craft::t('app', 'Edit GraphQL Token');
        } else {
            $token = new GqlToken();
            $token->accessToken = Craft::$app->getSecurity()->generateRandomString(32);
            $title = trim($token->name) ?: Craft::t('app', 'Create a new GraphQL token');
        }

        return $this->renderTemplate('graphql/tokens/_edit', compact(
            'token',
            'title'
        ));
    }

    /**
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\Exception
     */
    public function actionSaveToken()
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $gqlService = Craft::$app->getGql();
        $request = Craft::$app->getRequest();

        $tokenId = $request->getBodyParam('tokenId');

        if ($tokenId) {
            $token = $gqlService->getTokenById($tokenId);

            if (!$token) {
                throw new NotFoundHttpException('Token not found');
            }
        } else {
            $token = new GqlToken();
            $token->accessToken = $request->getRequiredBodyParam('accessToken');
        }

        $token->name = $request->getRequiredBodyParam('name');
        $token->enabled = (bool)$request->getRequiredBodyParam('enabled');
        $token->permissions = $request->getBodyParam('permissions');

        if (($expiryDate = $request->getBodyParam('expiryDate')) !== null) {
            $token->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }

        $session = Craft::$app->getSession();

        if (!$gqlService->saveToken($token)) {
            $session->setError(Craft::t('app', 'Couldn’t save token.'));

            // Send the volume back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'token' => $token
            ]);

            return null;
        }

        $session->setNotice(Craft::t('app', 'Token saved.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionDeleteToken(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $tokenId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Craft::$app->getGql()->deleteTokenById($tokenId);

        return $this->asJson(['success' => true]);
    }
}
