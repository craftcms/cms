<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\errors\GqlException;
use craft\errors\MissingComponentException;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\GqlSchema;
use craft\models\GqlToken;
use craft\models\Site;
use craft\services\Gql as GqlService;
use craft\web\assets\graphiql\GraphiqlAsset;
use craft\web\Controller;
use craft\web\ErrorHandler;
use craft\web\Response;
use DateTimeZone;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response as YiiResponse;

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
    protected array|bool|int $allowAnonymous = ['api'];

    /**
     * @inheritdoc
     */
    public $defaultAction = 'api';

    /**
     * @inheritdoc
     * @throws NotFoundHttpException
     */
    public function beforeAction($action): bool
    {
        if (!Craft::$app->getConfig()->getGeneral()->enableGql) {
            throw new NotFoundHttpException(Craft::t('yii', 'Page not found.'));
        }

        if ($action->id === 'api') {
            $this->enableCsrfValidation = false;
        }

        if (!parent::beforeAction($action)) {
            return false;
        }

        return true;
    }

    /**
     * Performs a GraphQL query.
     *
     * @return YiiResponse
     * @throws BadRequestHttpException
     * @throws GqlException
     * @throws ForbiddenHttpException
     */
    public function actionApi(): YiiResponse
    {
        // Add CORS headers
        $headers = $this->response->getHeaders();
        $headers->setDefault('Access-Control-Allow-Credentials', 'true');
        $headers->setDefault('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Craft-Authorization, X-Craft-Token');

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if (is_array($generalConfig->allowedGraphqlOrigins)) {
            if (($origins = $this->request->getOrigin()) !== null) {
                $origins = ArrayHelper::filterEmptyStringsFromArray(array_map('trim', explode(',', $origins)));
                foreach ($origins as $origin) {
                    if (in_array($origin, $generalConfig->allowedGraphqlOrigins)) {
                        $headers->setDefault('Access-Control-Allow-Origin', $origin);
                        break;
                    }
                }
            }
        } elseif ($generalConfig->allowedGraphqlOrigins !== false) {
            $headers->setDefault('Access-Control-Allow-Origin', '*');
        }

        if ($this->request->getIsOptions()) {
            // This is just a preflight request, no need to run the actual query yet
            $this->response->format = YiiResponse::FORMAT_RAW;
            $this->response->data = '';
            return $this->response;
        }

        $this->response->format = YiiResponse::FORMAT_JSON;

        $gqlService = Craft::$app->getGql();
        $schema = $this->_schema($gqlService);

        $this->_enforceSiteAccess($schema);

        $query = $operationName = $variables = null;

        // Check the body if it's a POST request
        if ($this->request->getIsPost()) {
            // If it's an application/graphql request, the whole body is the query
            if ($this->request->getIsGraphql()) {
                $query = $this->request->getRawBody();
            } else {
                $query = $this->request->getBodyParam('query');
                $operationName = $this->request->getBodyParam('operationName');
                $variables = $this->request->getBodyParam('variables');
            }
        }

        // query/variables/operationName GET params supersede BODY params
        if (($qQuery = $this->request->getQueryParam('query')) !== null) {
            $query = $qQuery;
        }

        if (($qVariables = $this->request->getQueryParam('variables')) !== null) {
            // Must be valid JSON
            try {
                $variables = Json::decode($qVariables);
            } catch (InvalidArgumentException $e) {
                throw new BadRequestHttpException('The variables param must be valid JSON', 0, $e);
            }
        }

        if (($qOperationName = $this->request->getQueryParam('operationName')) !== null) {
            $operationName = $qOperationName;
        }

        $queries = [];
        if ($singleQuery = ($query !== null)) {
            $queries[] = [$query, $variables, $operationName];
        } else {
            if ($this->request->getIsJson()) {
                // Check if there are any queries defined in the JSON body
                foreach ($this->request->getBodyParams() as $key => $param) {
                    $queries[$key] = [$param['query'] ?? null, $param['variables'] ?? null, $param['operationName'] ?? null];
                }
            }

            if (empty($queries)) {
                $singleQuery = true;
                $queries[] = [null, null, null];
            }
        }

        if ($generalConfig->maxGraphqlBatchSize && count($queries) > $generalConfig->maxGraphqlBatchSize) {
            throw new BadRequestHttpException(sprintf(
                'No more than %s GraphQL %s can be executed in a single batch.',
                $generalConfig->maxGraphqlBatchSize,
                $generalConfig->maxGraphqlBatchSize === 1 ? 'query' : 'queries'
            ));
        }


        // Generate all transforms immediately
        $generalConfig->generateTransformsBeforePageLoad = true;

        // Check for the cache-bust header
        $cacheHeader = $this->request->getHeaders()->get('x-craft-gql-cache');
        if ($cacheHeader === 'no-cache') {
            $cacheSetting = $generalConfig->enableGraphqlCaching;
            $generalConfig->enableGraphqlCaching = false;
        }

        $result = [];
        $hasMutations = false;

        foreach ($queries as $key => [$query, $variables, $operationName]) {
            $query = trim($query);
            try {
                if (empty($query)) {
                    throw new InvalidValueException('No GraphQL query was supplied');
                }
                $result[$key] = $gqlService->executeQuery($schema, $query, $variables, $operationName, App::devMode());
            } catch (InvalidValueException $e) {
                $result[$key] = [
                    'errors' => [
                        [
                            'message' => $e->getMessage(),
                        ],
                    ],
                ];
            } catch (Throwable $e) {
                /** @var ErrorHandler $errorHandler */
                $errorHandler = Craft::$app->getErrorHandler();
                $errorHandler->logException($e);
                $result[$key] = [
                    'errors' => [
                        $errorHandler->showExceptionDetails()
                            ? $errorHandler->exceptionAsArray($e)
                            : ['message' => Craft::t('app', 'Something went wrong when processing the GraphQL query.')],
                    ],
                ];
            }

            if (str_starts_with($query, 'mutation')) {
                $hasMutations = true;
            }
        }

        if (isset($cacheSetting)) {
            $generalConfig->enableGraphqlCaching = $cacheSetting;
        }

        $this->response->format = Response::FORMAT_GQL;
        $this->response->data = $singleQuery ? reset($result) : $result;

        // send cache headers
        $cache = isset($cacheHeader) ? $cacheHeader === 'cache' : !$hasMutations;
        if ($cache) {
            $this->response->setCacheHeaders();
        } else {
            $this->response->setNoCacheHeaders();
        }

        return $this->response;
    }

    /**
     * Returns the requested GraphQL schema
     *
     * @param GqlService $gqlService
     * @return GqlSchema
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     */
    private function _schema(GqlService $gqlService): GqlSchema
    {
        $requestHeaders = $this->request->getHeaders();

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

        $token = $this->_token($gqlService);

        // If we couldn't find a token, then return the active schema if there is one, otherwise bail
        if (!$token) {
            try {
                return $gqlService->getActiveSchema();
            } catch (GqlException) {
                throw new BadRequestHttpException('Missing Authorization header');
            }
        }

        // Update the lastUsed timestamp
        $now = DateTimeHelper::currentUTCDateTime();
        if (
            !$token->lastUsed ||
            $token->lastUsed->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i') !== $now->format('Y-m-d H:i')
        ) {
            $token->lastUsed = $now;
            $gqlService->saveToken($token);
        }

        return $token->getSchema();
    }

    private function _token(GqlService $gqlService): ?GqlToken
    {
        $bearerToken = $this->request->getBearerToken();

        if ($bearerToken) {
            try {
                $token = $gqlService->getTokenByAccessToken($bearerToken);

                if (!$token->getIsValid()) {
                    throw new BadRequestHttpException('Invalid Authorization header');
                }

                return $token;
            } catch (InvalidArgumentException) {
            }
        }

        // Get the public schema, if it exists & is valid
        return $this->_publicToken($gqlService);
    }

    /**
     * Returns the public token, if it exists and is valid.
     *
     * @param GqlService $gqlService
     * @return GqlToken|null
     */
    private function _publicToken(GqlService $gqlService): ?GqlToken
    {
        try {
            $token = $gqlService->getPublicToken();
        } catch (Throwable $e) {
            Craft::warning('Could not obtain the public token: ' . $e->getMessage());
            Craft::$app->getErrorHandler()->logException($e);
            return null;
        }

        return $token->getIsValid() ? $token : null;
    }

    /**
     * Enforce site access based on used schema.
     *
     * @param GqlSchema $schema
     * @return void
     * @throws ForbiddenHttpException
     * @throws \craft\errors\SiteNotFoundException
     */
    private function _enforceSiteAccess(GqlSchema $schema): void
    {
        $sitesService = Craft::$app->getSites();
        $allowedSites = GqlHelper::getAllowedSites($schema);
        $allowedSiteIds = array_flip(array_map(fn(Site $site) => $site->id, $allowedSites));

        // check if schema has access to the current site
        $currentSite = $sitesService->getCurrentSite();
        if (isset($allowedSiteIds[$currentSite->id])) {
            return;
        }

        // if not, check if it has access to the primary site (if different from the current site)
        $primarySite = $sitesService->getPrimarySite();
        if ($currentSite->id !== $primarySite->id && isset($allowedSiteIds[$primarySite->id])) {
            $sitesService->setCurrentSite($primarySite);
            return;
        }

        // otherwise, loop through all sites until we find one that the token has access to
        foreach ($sitesService->getAllSites() as $site) {
            if (isset($allowedSiteIds[$site->id])) {
                $sitesService->setCurrentSite($site);
                return;
            }
        }

        // no allowed sites could be found, so throw a ForbiddenHttpException
        throw new ForbiddenHttpException(sprintf('Schema doesn’t have access to the “%s” site.', $currentSite->getName()));
    }

    /**
     * @return YiiResponse
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws BadRequestHttpException
     */
    public function actionGraphiql(): YiiResponse
    {
        $this->requireAdmin(false);
        $this->getView()->registerAssetBundle(GraphiqlAsset::class);

        $schemaUid = $this->request->getQueryParam('schemaUid');
        $gqlService = Craft::$app->getGql();

        // Ensure the public schema is created.
        Craft::$app->getGql()->getPublicSchema();

        if ($schemaUid && $schemaUid !== '*') {
            try {
                $selectedSchema = $gqlService->getSchemaByUid($schemaUid);
            } catch (InvalidArgumentException) {
                throw new BadRequestHttpException('Invalid token UID.');
            }
            Craft::$app->getSession()->authorize("graphql-schema:$schemaUid");
        } else {
            $selectedSchema = GqlHelper::createFullAccessSchema();
        }

        $schemas = [
            [
                'label' => Craft::t('app', 'Full Schema'),
                'value' => '*',
            ],
        ];

        foreach ($gqlService->getSchemas() as $schema) {
            $schemas[] = [
                'label' => $schema->name,
                'value' => $schema->uid,
            ];
        }

        return $this->renderTemplate('graphql/graphiql.twig', [
            'url' => UrlHelper::actionUrl('graphql/api'),
            'schemas' => $schemas,
            'selectedSchema' => $selectedSchema,
        ]);
    }

    /**
     * Redirects to the GraphQL Schemas/Tokens page in the control panel.
     *
     * @return YiiResponse
     * @throws NotFoundHttpException if this isn't a control panel request
     * @throws ForbiddenHttpException if the logged-in user isn't an admin
     * @since 3.5.0
     */
    public function actionCpIndex(): YiiResponse
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if (!$this->request->getIsCpRequest() || !$generalConfig->enableGql) {
            throw new NotFoundHttpException();
        }

        $this->requireAdmin(false);

        if ($generalConfig->allowAdminChanges) {
            return $this->redirect('graphql/schemas');
        }

        return $this->redirect('graphql/tokens');
    }

    /**
     * @return YiiResponse
     * @throws ForbiddenHttpException
     * @since 3.4.0
     */
    public function actionViewSchemas(): YiiResponse
    {
        $this->requireAdmin();

        // Ensure the public schema is created.
        Craft::$app->getGql()->getPublicSchema();

        return $this->renderTemplate('graphql/schemas/_index.twig');
    }

    /**
     * @param int|null $tokenId
     * @param GqlToken|null $token
     * @return YiiResponse
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @since 3.4.0
     */
    public function actionEditToken(?int $tokenId = null, ?GqlToken $token = null): YiiResponse
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

            $title = trim($token->name ?? '') ?: Craft::t('app', 'Edit GraphQL Token');
        } else {
            $token = new GqlToken();
            $accessToken = $this->_generateToken();
            $title = trim($token->name ?? '') ?: Craft::t('app', 'Create a new GraphQL token');
        }

        $schemas = $gqlService->getSchemas();

        $schemaOptions = [];

        $publicSchema = $gqlService->getPublicSchema();

        foreach ($schemas as $schema) {
            if (!$publicSchema || $schema->id !== $publicSchema->id) {
                $schemaOptions[] = [
                    'label' => $schema->name,
                    'value' => $schema->id,
                ];
            }
        }

        if ($token->id && !$token->schemaId && !empty($schemaOptions)) {
            // Add a blank option to the top so it's clear no schema is currently selected
            array_unshift($schemaOptions, [
                'label' => '',
                'value' => '',
            ]);
        }

        return $this->renderTemplate('graphql/tokens/_edit.twig', compact(
            'token',
            'title',
            'accessToken',
            'schemaOptions'
        ));
    }

    /**
     * @return YiiResponse|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws MissingComponentException
     * @throws Exception
     * @since 3.4.0
     */
    public function actionSaveToken(): ?YiiResponse
    {
        $this->requirePostRequest();
        $this->requireAdmin(false);
        $this->requireElevatedSession();

        $gqlService = Craft::$app->getGql();
        $tokenId = $this->request->getBodyParam('tokenId');

        if ($tokenId) {
            $token = $gqlService->getTokenById($tokenId);

            if (!$token) {
                throw new NotFoundHttpException('Token not found');
            }
        } else {
            $token = new GqlToken();
        }

        $token->name = $this->request->getBodyParam('name') ?? $token->name;
        $token->accessToken = $this->request->getBodyParam('accessToken') ?? $token->accessToken;
        $token->enabled = (bool)$this->request->getRequiredBodyParam('enabled');
        $token->schemaId = $this->request->getBodyParam('schema');

        if (($expiryDate = $this->request->getBodyParam('expiryDate')) !== null) {
            $token->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }

        if (!$gqlService->saveToken($token)) {
            return $this->asFailure(
                Craft::t('app', 'Couldn’t save token.'),
                routeParams: [
                    'token' => $token,
                ]
            );
        }

        return $this->asSuccess(Craft::t('app', 'Schema saved.'));
    }

    /**
     * @return YiiResponse
     * @throws BadRequestHttpException
     * @since 3.4.0
     */
    public function actionDeleteToken(): YiiResponse
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin(false);

        $schemaId = $this->request->getRequiredBodyParam('id');

        Craft::$app->getGql()->deleteTokenById($schemaId);

        return $this->asSuccess();
    }


    /**
     * @return YiiResponse
     * @throws ForbiddenHttpException
     * @since 3.4.0
     */
    public function actionViewTokens(): YiiResponse
    {
        $this->requireAdmin(false);
        return $this->renderTemplate('graphql/tokens/_index.twig');
    }

    /**
     * @param int|null $schemaId
     * @param GqlSchema|null $schema
     * @return YiiResponse
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @since 3.4.0
     */
    public function actionEditSchema(?int $schemaId = null, ?GqlSchema $schema = null): YiiResponse
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

        return $this->renderTemplate('graphql/schemas/_edit.twig', compact(
            'schema',
            'title'
        ));
    }

    /**
     * @param GqlSchema|null $schema
     * @return YiiResponse
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @since 3.4.0
     */
    public function actionEditPublicSchema(?GqlSchema $schema = null): YiiResponse
    {
        $this->requireAdmin();

        $gqlService = Craft::$app->getGql();

        if (!$schema) {
            $schema = $gqlService->getPublicSchema();
        }

        $token = $gqlService->getPublicToken();
        $title = Craft::t('app', 'Edit the public GraphQL schema');

        return $this->renderTemplate('graphql/schemas/_edit.twig', compact(
            'schema',
            'token',
            'title'
        ));
    }

    /**
     * @return YiiResponse|null
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @since 3.4.0
     */
    public function actionSavePublicSchema(): ?YiiResponse
    {
        $this->requirePostRequest();
        $this->requireAdmin();
        $this->requireElevatedSession();

        $gqlService = Craft::$app->getGql();
        $schema = $gqlService->getPublicSchema();
        $schema->scope = $this->request->getBodyParam('permissions') ?? [];

        if (!$gqlService->saveSchema($schema)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save schema.'));

            // Send the schema back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'schema' => $schema,
            ]);

            return null;
        }

        $token = $gqlService->getPublicToken();
        $token->enabled = (bool)$this->request->getRequiredBodyParam('enabled');

        if (($expiryDate = $this->request->getBodyParam('expiryDate')) !== null) {
            $token->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }

        if (!$gqlService->saveToken($token)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save public schema settings.'));

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Schema saved.'));
        return $this->redirectToPostedUrl();
    }

    /**
     * @return YiiResponse|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws MissingComponentException
     * @throws Exception
     * @since 3.4.0
     */
    public function actionSaveSchema(): ?YiiResponse
    {
        $this->requirePostRequest();
        $this->requireAdmin();
        $this->requireElevatedSession();

        $gqlService = Craft::$app->getGql();
        $schemaId = $this->request->getBodyParam('schemaId');

        if ($schemaId) {
            $schema = $gqlService->getSchemaById($schemaId);

            if (!$schema) {
                throw new NotFoundHttpException('Schema not found');
            }
        } else {
            $schema = new GqlSchema();
        }

        $schema->name = $this->request->getBodyParam('name') ?? $schema->name;
        $schema->scope = $this->request->getBodyParam('permissions') ?? [];

        if (!$gqlService->saveSchema($schema)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save schema.'));

            // Send the schema back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'schema' => $schema,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Schema saved.'));
        return $this->redirectToPostedUrl($schema);
    }

    /**
     * @return YiiResponse
     * @throws BadRequestHttpException
     * @since 3.4.0
     */
    public function actionDeleteSchema(): YiiResponse
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin();

        $schemaId = $this->request->getRequiredBodyParam('id');

        Craft::$app->getGql()->deleteSchemaById($schemaId);

        return $this->asSuccess();
    }

    /**
     * @return YiiResponse
     * @throws BadRequestHttpException
     */
    public function actionFetchToken(): YiiResponse
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin(false);
        $this->requireElevatedSession();

        $tokenUid = $this->request->getRequiredBodyParam('tokenUid');

        try {
            $schema = Craft::$app->getGql()->getTokenByUid($tokenUid);
        } catch (InvalidArgumentException) {
            throw new BadRequestHttpException('Invalid schema UID.');
        }

        return $this->asJson([
            'accessToken' => $schema->accessToken,
        ]);
    }

    /**
     * @return YiiResponse
     */
    public function actionGenerateToken(): YiiResponse
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
