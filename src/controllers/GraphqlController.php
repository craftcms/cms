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
use craft\helpers\Gql;
use craft\helpers\UrlHelper;
use craft\models\GqlToken;
use craft\models\GqlSchema;
use craft\web\assets\graphiql\GraphiqlAsset;
use craft\web\Controller;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The GqlController class is a controller that handles various GraphQL related tasks.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class GraphqlController extends Controller
{
    /**
     * @inheritdoc
     */
    public $allowAnonymous = ['api'];

    /**
     * @inheritdoc
     */
    public $defaultAction = 'api';

    /**
     * @inheritdoc
     * @throws NotFoundHttpException
     */
    public function beforeAction($action)
    {
        if (!Craft::$app->getConfig()->getGeneral()->enableGql) {
            throw new NotFoundHttpException(Craft::t('yii', 'Page not found.'));
        }

        Craft::$app->requireEdition(Craft::Pro);

        if ($action->id === 'api') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    /**
     * Performs a GraphQL query.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws GqlException
     * @throws ForbiddenHttpException
     */
    public function actionApi(): Response
    {
        $request = Craft::$app->getRequest();
        $response = Craft::$app->getResponse();

        // Add CORS headers
        $response->getHeaders()
            ->add('Access-Control-Allow-Origin', $request->getOrigin())
            ->add('Access-Control-Allow-Credentials', 'true');

        if ($request->getIsOptions()) {
            // This is just a preflight request, no need to run the actual query yet
            $response->getHeaders()->add('Access-Control-Allow-Headers', 'Authorization, Content-Type');
            $response->format = Response::FORMAT_RAW;
            $response->data = '';
            return $response;
        }

        $response->format = Response::FORMAT_JSON;

        $gqlService = Craft::$app->getGql();

        $token = null;
        $schema = null;
        $authorizationHeader = Craft::$app->request->headers->get('authorization');

        if (preg_match('/^Bearer\s+(.+)$/i', $authorizationHeader, $matches)) {
            $token = $matches[1];
            if ($token === '*') {
                $this->requireAdmin(false);
                $token = Gql::createFullAccessToken();
            } else {
                try {
                    $token = $gqlService->getTokenByAccessToken($token);
                } catch (InvalidArgumentException $e) {
                    throw new BadRequestHttpException('Invalid authorization token.');
                }
            }
        }

        // What if something already set it on the service?
        if (!$token) {
            try {
                $schema = $gqlService->getActiveSchema();
            } catch (GqlException $exception) {
                // Well, go for the public token then.
                $token = $gqlService->getPublicToken();
            }
        }

        if (!$schema) {
            $schemaExpired = $token && $token->expiryDate && $token->expiryDate->getTimestamp() <= DateTimeHelper::currentTimeStamp();

            if (!$token || !$token->enabled || $schemaExpired) {
                throw new ForbiddenHttpException('Invalid authorization token.');
            }

            $schema = $token->getSchema();
        }

        $query = $operationName = $variables = null;

        // Check the body if it's a POST request
        if ($request->getIsPost()) {
            // If it's a application/graphql request, the whole body is the query
            if ($request->getContentType() === 'application/graphql') {
                $query = $request->getRawBody();
            } else {
                $query = $request->getBodyParam('query');
                $operationName = $request->getBodyParam('operationName');
                $variables = $request->getBodyParam('variables');
            }
        }

        // 'query' GET param supersedes all others though
        $query = $request->getQueryParam('query', $query);

        // 400 error if we couldn't find the query
        if ($query === null) {
            throw new BadRequestHttpException('No GraphQL query was supplied.');
        }

        try {
            $result = $gqlService->executeQuery($schema, $query, $variables, $operationName, Craft::$app->getConfig()->getGeneral()->devMode);
        } catch (\Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);

            $result = [
                'errors' => [
                    [
                        'message' => Craft::$app->getConfig()->getGeneral()->devMode ? $e->getMessage() : Craft::t('app', 'Something went wrong when processing the GraphQL query.'),
                    ]
                ],
            ];
        }

        return $this->asJson($result);
    }

    /**
     * @return Response
     * @throws ForbiddenHttpException
     * @throws \yii\base\InvalidConfigException
     * @throws BadRequestHttpException
     */
    public function actionGraphiql(): Response
    {
        $this->requireAdmin(false);
        $this->getView()->registerAssetBundle(GraphiqlAsset::class);

        $tokenUid = Craft::$app->getRequest()->getQueryParam('tokenUid');
        $gqlService = Craft::$app->getGql();

        if ($tokenUid && $tokenUid !== '*') {
            try {
                $selectedToken = $gqlService->getTokenByUid($tokenUid);
            } catch (InvalidArgumentException $e) {
                throw new BadRequestHttpException('Invalid token UID.');
            }
            Craft::$app->getSession()->authorize("graphql-token:{$tokenUid}");
        } else {
            $selectedToken = Gql::createFullAccessToken();
        }

        $tokens = [
            Craft::t('app', 'Full Access Token') => '*',
        ];

        foreach ($gqlService->getTokens() as $token) {
            $name = $token->getIsPublic() ? Craft::t('app', 'Public Token') : $token->name;
            $tokens[$name] = $token->uid;
        }

        return $this->renderTemplate('graphql/graphiql', [
            'url' => UrlHelper::actionUrl('graphql/api'),
            'tokens' => $tokens,
            'selectedToken' => $selectedToken
        ]);
    }

    /**
     * @return Response
     * @throws ForbiddenHttpException
     * @since 3.4.0
     */
    public function actionViewSchemas(): Response
    {
        $this->requireAdmin(false);
        return $this->renderTemplate('graphql/schemas/_index');
    }

    /**
     * @param int|null $tokenId
     * @param GqlToken|null $token
     * @return Response
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @since 3.4.0
     */
    public function actionEditToken(int $tokenId = null, GqlToken $token = null): Response
    {
        $this->requireAdmin(false);

        $gqlService = Craft::$app->getGql();
        $accessToken = null;

        if ($token || $tokenId) {
            if (!$token) {
                $token = $gqlService->getTokenById($tokenId);
            }

            if (!$token) {
                throw new NotFoundHttpException('Token not found');
            }

            if ($token->getIsPublic()) {
                $title = Craft::t('app', 'Edit the Public GraphQL Token');
            } else {
                $title = trim($token->name) ?: Craft::t('app', 'Edit GraphQL Token');
            }
        } else {
            $token = new GqlToken();
            $accessToken = $this->_generateToken();
            $title = trim($token->name) ?: Craft::t('app', 'Create a new GraphQL token');
        }

        $schemas = $gqlService->getSchemas();

        $schemaOptions = [
            ['label' => '-', 'value' => null,]
        ];

        foreach ($schemas as $schema) {
            $schemaOptions[] = [
                'label' => $schema->name,
                'value' => $schema->id
            ];
        }

        return $this->renderTemplate('graphql/tokens/_edit', compact(
            'token',
            'title',
            'accessToken',
            'schemaOptions'
        ));
    }

    /**
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\Exception
     * @since 3.4.0
     */
    public function actionSaveToken()
    {
        $this->requirePostRequest();
        $this->requireAdmin(false);
        $this->requireElevatedSession();

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
        }

        $token->name = $request->getBodyParam('name') ?? $token->name;
        $token->accessToken = $request->getBodyParam('accessToken') ?? $token->accessToken;
        $token->enabled = (bool)$request->getRequiredBodyParam('enabled');
        $token->schemaId = $request->getBodyParam('schema');

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

        $session->setNotice(Craft::t('app', 'Schema saved.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     * @since 3.4.0
     */
    public function actionDeleteToken(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin(false);

        $schemaId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Craft::$app->getGql()->deleteTokenById($schemaId);

        return $this->asJson(['success' => true]);
    }


    /**
     * @return Response
     * @throws ForbiddenHttpException
     * @since 3.4.0
     */
    public function actionViewTokens(): Response
    {
        $this->requireAdmin();
        return $this->renderTemplate('graphql/tokens/_index');
    }

    /**
     * @param int|null $schemaId
     * @param GqlSchema|null $schema
     * @return Response
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @since 3.4.0
    */
    public function actionEditSchema(int $schemaId = null, GqlSchema $schema = null): Response
    {
        $this->requireAdmin();

        $gqlService = Craft::$app->getGql();

        if ($schema || $schemaId) {
            if (!$schema) {
                $schema = $gqlService->getSchemaById($schemaId);
            }

            if (!$schema) {
                throw new NotFoundHttpException('Schema not found');
            }

            $title = trim($schema->name) ?: Craft::t('app', 'Edit GraphQL Schema');
        } else {
            $schema = new GqlSchema();
            $title = trim($schema->name) ?: Craft::t('app', 'Create a new GraphQL Schema');
        }


        return $this->renderTemplate('graphql/schemas/_edit', compact(
            'schema',
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
     * @since 3.4.0
     */
    public function actionSaveSchema()
    {
        $this->requirePostRequest();
        $this->requireAdmin();
        $this->requireElevatedSession();

        $gqlService = Craft::$app->getGql();
        $request = Craft::$app->getRequest();

        $schemaId = $request->getBodyParam('schemaId');

        if ($schemaId) {
            $schema = $gqlService->getSchemaById($schemaId);

            if (!$schema) {
                throw new NotFoundHttpException('Schema not found');
            }
        } else {
            $schema = new GqlSchema();
        }

        $schema->name = $request->getBodyParam('name') ?? $schema->name;
        $schema->scope = $request->getBodyParam('permissions');
        $session = Craft::$app->getSession();

        if (!$gqlService->saveSchema($schema)) {
            $session->setError(Craft::t('app', 'Couldn’t save schema.'));

            // Send the volume back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'schema' => $schema
            ]);

            return null;
        }

        $session->setNotice(Craft::t('app', 'Schema saved.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     * @since 3.4.0
     */
    public function actionDeleteSchema(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin();

        $schemaId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Craft::$app->getGql()->deleteSchemaById($schemaId);

        return $this->asJson(['success' => true]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionFetchToken(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin(false);
        $this->requireElevatedSession();

        $schemaUid = Craft::$app->getRequest()->getRequiredBodyParam('schemaUid');

        try {
            $schema = Craft::$app->getGql()->getTokenByUid($schemaUid);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException('Invalid schema UID.');
        }

        return $this->asJson([
            'accessToken' => $schema->accessToken,
        ]);
    }

    /**
     * @return Response
     */
    public function actionGenerateToken(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin(false);

        return $this->asJson([
            'accessToken' => $this->_generateToken(),
        ]);
    }

    /**
     * @return string
     */
    private function _generateToken(): string
    {
        return Craft::$app->getSecurity()->generateRandomString(32);
    }
}
