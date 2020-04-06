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
use craft\helpers\Gql as GqlHelper;
use craft\helpers\UrlHelper;
use craft\models\GqlSchema;
use craft\models\GqlToken;
use craft\services\Gql as GqlService;
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
        $schema = $this->_schema($gqlService);
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
            throw new BadRequestHttpException('No GraphQL query was supplied');
        }

        // Generate all transforms immediately
        Craft::$app->getConfig()->getGeneral()->generateTransformsBeforePageLoad = true;

        try {
            $result = $gqlService->executeQuery($schema, $query, $variables, $operationName, YII_DEBUG);
        } catch (\Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);

            $result = [
                'errors' => [
                    [
                        'message' => YII_DEBUG ? $e->getMessage() : Craft::t('app', 'Something went wrong when processing the GraphQL query.'),
                    ]
                ],
            ];
        }

        return $this->asJson($result);
    }

    /**
     * Returns the requested GraphQL schema
     *
     * @param GqlService $gqlService
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     */
    private function _schema(GqlService $gqlService): GqlSchema
    {
        $request = Craft::$app->getRequest();
        $requestHeaders = $request->getHeaders();

        // Admins can access schemas directly with a X-Craft-Gql-Schema header
        if ($requestHeaders->has('x-craft-gql-schema')) {
            $this->requireAdmin(false);
            $schemaUid = $requestHeaders->get('x-craft-gql-schema');
            if ($schemaUid === '*') {
                return GqlHelper::createFullAccessSchema();
            }
            $schema = $gqlService->getSchemaByUid($schemaUid);
            if (!$schema) {
                throw new BadRequestHttpException('Invalid X-Craft-Gql-Schema header');
            }
            return $schema;
        }

        // Was a specific token passed?
        if ($requestHeaders->has('authorization')) {
            if (preg_match('/^Bearer\s+(.+)$/i', $requestHeaders->get('authorization'), $matches)) {
                try {
                    $token = $gqlService->getTokenByAccessToken($matches[1]);
                } catch (InvalidArgumentException $e) {
                }
            }
            if (!isset($token) || !$token->getIsValid()) {
                throw new BadRequestHttpException('Invalid Authorization header');
            }
        } else {
            // Get the public schema, if it exists & is valid
            $token = $this->_publicToken($gqlService);

            // If we couldn't find a token, then return the active schema if there is one, otherwise bail
            if (!$token) {
                try {
                    return $gqlService->getActiveSchema();
                } catch (GqlException $exception) {
                    throw new BadRequestHttpException('Missing Authorization header');
                }
            }
        }

        // Update the lastUsed timestamp
        $token->lastUsed = DateTimeHelper::currentUTCDateTime();
        $gqlService->saveToken($token);

        return $token->getSchema();
    }

    /**
     * Returns the public token, if it exists and is valid.
     *
     * @param GqlService $gqlService
     * @return GqlToken|null
     */
    private function _publicToken(GqlService $gqlService)
    {
        try {
            $token = $gqlService->getTokenByAccessToken(GqlToken::PUBLIC_TOKEN);
        } catch (InvalidArgumentException $e) {
            return null;
        }
        return $token->getIsValid() ? $token : null;
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

        $schemaUid = Craft::$app->getRequest()->getQueryParam('schemaUid');
        $gqlService = Craft::$app->getGql();

        // Ensure the public schema is created.
        Craft::$app->getGql()->getPublicSchema();

        if ($schemaUid && $schemaUid !== '*') {
            try {
                $selectedSchema = $gqlService->getSchemaByUid($schemaUid);
            } catch (InvalidArgumentException $e) {
                throw new BadRequestHttpException('Invalid token UID.');
            }
            Craft::$app->getSession()->authorize("graphql-schema:{$schemaUid}");
        } else {
            $selectedSchema = GqlHelper::createFullAccessSchema();
        }

        $schemas = [
            Craft::t('app', 'Full Schema') => '*',
        ];

        foreach ($gqlService->getSchemas() as $schema) {
            $name = $schema->name;
            $schemas[$name] = $schema->uid;
        }

        return $this->renderTemplate('graphql/graphiql', [
            'url' => UrlHelper::actionUrl('graphql/api'),
            'schemas' => $schemas,
            'selectedSchema' => $selectedSchema
        ]);
    }

    /**
     * @return Response
     * @throws ForbiddenHttpException
     * @since 3.4.0
     */
    public function actionViewSchemas(): Response
    {
        $this->requireAdmin();

        // Ensure the public schema is created.
        Craft::$app->getGql()->getPublicSchema();

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

            if (!$token || $token->getIsPublic()) {
                throw new NotFoundHttpException('Token not found');
            }

            $title = trim($token->name) ?: Craft::t('app', 'Edit GraphQL Token');
        } else {
            $token = new GqlToken();
            $accessToken = $this->_generateToken();
            $title = trim($token->name) ?: Craft::t('app', 'Create a new GraphQL token');
        }

        $schemas = $gqlService->getSchemas();

        $schemaOptions = [];

        $publicSchema = $gqlService->getPublicSchema();

        foreach ($schemas as $schema) {
            if (!$publicSchema || $schema->id !== $publicSchema->id) {
                $schemaOptions[] = [
                    'label' => $schema->name,
                    'value' => $schema->id
                ];
            }
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

            // Send the token back to the template
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
        $this->requireAdmin(false);
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
     * @param GqlSchema|null $schema
     * @return Response
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @since 3.4.0
     */
    public function actionEditPublicSchema(GqlSchema $schema = null): Response
    {
        $this->requireAdmin();

        $gqlService = Craft::$app->getGql();

        if (!$schema) {
            $schema = $gqlService->getPublicSchema();
        }

        $token = $gqlService->getTokenByAccessToken(GqlToken::PUBLIC_TOKEN);

        if (!$token) {
            throw new NotFoundHttpException('Public schema not found');
        }

        $title = Craft::t('app', 'Edit the public GraphQL schema');

        return $this->renderTemplate('graphql/schemas/_edit', compact(
            'schema',
            'token',
            'title'
        ));
    }

    /**
     * @return Response
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @since 3.4.0
     */
    public function actionSavePublicSchema(): Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();
        $this->requireElevatedSession();

        $gqlService = Craft::$app->getGql();
        $request = Craft::$app->getRequest();

        $schema = $gqlService->getPublicSchema();
        $schema->scope = $request->getBodyParam('permissions');
        $session = Craft::$app->getSession();

        if (!$gqlService->saveSchema($schema)) {
            $session->setError(Craft::t('app', 'Couldn’t save schema.'));

            // Send the schema back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'schema' => $schema
            ]);

            return null;
        }

        $token = $gqlService->getTokenByAccessToken(GqlToken::PUBLIC_TOKEN);
        $token->enabled = (bool)$request->getRequiredBodyParam('enabled');

        if (($expiryDate = $request->getBodyParam('expiryDate')) !== null) {
            $token->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }

        if (!$gqlService->saveToken($token)) {
            $session->setError(Craft::t('app', 'Couldn’t save public schema settings.'));

            return null;
        }

        $session->setNotice(Craft::t('app', 'Schema saved.'));

        return $this->redirectToPostedUrl();
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

            // Send the schema back to the template
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

        $tokenUid = Craft::$app->getRequest()->getRequiredBodyParam('tokenUid');

        try {
            $schema = Craft::$app->getGql()->getTokenByUid($tokenUid);
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
