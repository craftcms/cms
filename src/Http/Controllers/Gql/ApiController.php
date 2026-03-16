<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Gql;

use craft\helpers\DateTimeHelper;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Gql\Exceptions\GqlException;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Http\Responses\GqlResponse;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Sites as SiteSites;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

use function CraftCms\Cms\t;

readonly class ApiController extends GqlController
{
    public function __construct(
        private GeneralConfig $generalConfig,
        private Gql $gql,
        private SiteSites $sites,
    ) {}

    public function __invoke(Request $request): Response
    {
        $this->ensureGqlEnabled();

        $schema = $this->resolveSchema($request);

        $this->enforceSiteAccess($schema);

        [$singleQuery, $queries] = $this->resolveQueries($request);

        if ($this->generalConfig->maxGraphqlBatchSize && count($queries) > $this->generalConfig->maxGraphqlBatchSize) {
            abort(400, sprintf(
                'No more than %s GraphQL %s can be executed in a single batch.',
                $this->generalConfig->maxGraphqlBatchSize,
                Str::plural('query', $this->generalConfig->maxGraphqlBatchSize),
            ));
        }

        $this->generalConfig->generateTransformsBeforePageLoad = true;

        $cacheHeader = $request->headers->get('x-craft-gql-cache');
        $cache = $request->headers->get('x-craft-gql-cache') ? ($cacheHeader === 'cache') : null;

        if ($cache !== null) {
            $cacheSetting = $this->generalConfig->enableGraphqlCaching;
            $this->generalConfig->enableGraphqlCaching = $cache;
        }

        $result = [];
        $hasMutations = false;

        foreach ($queries as $key => [$query, $variables, $operationName]) {
            $query = is_string($query) ? trim($query) : trim((string) ($query ?? ''));

            try {
                if ($query === '') {
                    throw new ValueError('No GraphQL query was supplied');
                }

                $result[$key] = $this->gql->executeQuery(
                    $schema,
                    $query,
                    is_array($variables) ? $variables : null,
                    is_string($operationName) ? $operationName : null,
                    app()->hasDebugModeEnabled(),
                );
            } catch (ValueError $e) {
                $result[$key] = [
                    'errors' => [[
                        'message' => $e->getMessage(),
                    ]],
                ];
            } catch (Throwable $e) {
                report($e);

                $result[$key] = [
                    'errors' => [
                        $this->shouldShowExceptionDetails()
                            ? $this->exceptionAsArray($e)
                            : ['message' => t('Something went wrong when processing the GraphQL query.')],
                    ],
                ];
            }

            if (str_starts_with($query, 'mutation')) {
                $hasMutations = true;
            }
        }

        if (isset($cacheSetting)) {
            $this->generalConfig->enableGraphqlCaching = $cacheSetting;
        }

        $response = new GqlResponse($singleQuery ? reset($result) : $result);

        if (! ($cache ?? ! $hasMutations)) {
            $response->nocache();
        }

        return $response;
    }

    /**
     * @return array{0:bool,1:array<int|string,array{0:mixed,1:mixed,2:mixed}>}
     */
    private function resolveQueries(Request $request): array
    {
        $query = $operationName = $variables = null;

        if ($request->isMethod('POST')) {
            if ($this->normalizedContentType($request) === 'application/graphql') {
                $query = $request->getContent();
            } else {
                $query = $request->input('query');
                $operationName = $request->input('operationName');
                $variables = $request->input('variables');
            }
        }

        if (($queryOverride = $request->query('query')) !== null) {
            $query = $queryOverride;
        }

        if (($variablesOverride = $request->query('variables')) !== null) {
            try {
                $variables = Json::decode($variablesOverride);
            } catch (InvalidArgumentException) {
                abort(400, 'The variables param must be valid JSON');
            }
        }

        if (($operationNameOverride = $request->query('operationName')) !== null) {
            $operationName = $operationNameOverride;
        }

        $queries = [];
        $singleQuery = $query !== null;

        if ($singleQuery) {
            $queries[] = [$query, $variables, $operationName];
        } else {
            if ($this->normalizedContentType($request) === 'application/json') {
                foreach ($request->json()->all() as $key => $param) {
                    $queries[$key] = [
                        is_array($param) ? ($param['query'] ?? null) : null,
                        is_array($param) ? ($param['variables'] ?? null) : null,
                        is_array($param) ? ($param['operationName'] ?? null) : null,
                    ];
                }
            }

            if ($queries === []) {
                $singleQuery = true;
                $queries[] = [null, null, null];
            }
        }

        return [$singleQuery, $queries];
    }

    private function resolveSchema(Request $request): GqlSchema
    {
        $requestHeaders = $request->headers;

        if ($requestHeaders->has('X-Craft-Gql-Schema')) {
            $user = $request->user('craft');

            abort_if(! $user, 401, 'Unauthenticated.');
            abort_unless($user->isAdmin(), 403, 'User is not permitted to perform this action.');

            $schemaUid = (string) $requestHeaders->get('X-Craft-Gql-Schema');

            if ($schemaUid === '*') {
                return GqlHelper::createFullAccessSchema();
            }

            $schema = $this->gql->getSchemaByUid($schemaUid);

            abort_if(is_null($schema), 400, 'Invalid X-Craft-Gql-Schema header');

            return $schema;
        }

        $token = $this->resolveToken($request, $this->gql);

        if (! $token) {
            try {
                return $this->gql->getActiveSchema();
            } catch (GqlException) {
                abort(400, 'Missing Authorization header');
            }
        }

        $now = DateTimeHelper::currentUTCDateTime();

        if (
            ! $token->lastUsed ||
            $token->lastUsed->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i') !== $now->format('Y-m-d H:i')
        ) {
            $token->lastUsed = $now;
            $this->gql->saveToken($token);
        }

        return $token->getSchema();
    }

    private function resolveToken(Request $request, Gql $gqlService): ?GqlToken
    {
        $bearerToken = $request->bearerToken();

        if ($bearerToken) {
            try {
                $token = $gqlService->getTokenByAccessToken($bearerToken);

                abort_if(! $token->getIsValid(), 400, 'Invalid Authorization header');

                return $token;
            } catch (InvalidArgumentException) {
            }
        }

        return $this->resolvePublicToken($gqlService);
    }

    private function resolvePublicToken(Gql $gqlService): ?GqlToken
    {
        try {
            $token = $gqlService->getPublicToken();
        } catch (Throwable $e) {
            Log::info('Could not obtain the public token: '.$e->getMessage());
            report($e);

            return null;
        }

        return $token && $token->getIsValid() ? $token : null;
    }

    private function enforceSiteAccess(GqlSchema $schema): void
    {
        $allowedSites = GqlHelper::getAllowedSites($schema);
        $allowedSiteIds = array_flip(array_map(fn (Site $site) => $site->id, $allowedSites));

        $currentSite = $this->sites->getCurrentSite();

        if (isset($allowedSiteIds[$currentSite->id])) {
            return;
        }

        $primarySite = $this->sites->getPrimarySite();

        if ($currentSite->id !== $primarySite->id && isset($allowedSiteIds[$primarySite->id])) {
            $this->sites->setCurrentSite($primarySite);

            return;
        }

        foreach ($this->sites->getAllSites() as $site) {
            if (isset($allowedSiteIds[$site->id])) {
                $this->sites->setCurrentSite($site);

                return;
            }
        }

        abort(403, sprintf('Schema doesn’t have access to the “%s” site.', $currentSite->getName()));
    }

    private function normalizedContentType(Request $request): ?string
    {
        $contentType = $request->headers->get('Content-Type');

        if ($contentType === null) {
            return null;
        }

        if (($pos = strpos($contentType, ';')) !== false) {
            $contentType = substr($contentType, 0, $pos);
        }

        return strtolower(trim($contentType));
    }

    private function exceptionAsArray(Throwable $e): array
    {
        $array = [
            'exception' => $e::class,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => array_map(function (array $step) {
                unset($step['args']);

                return $step;
            }, $e->getTrace()),
        ];

        if ($e->getPrevious() !== null) {
            $array['previous'] = $this->exceptionAsArray($e->getPrevious());
        }

        return $array;
    }

    private function shouldShowExceptionDetails(): bool
    {
        if (app()->hasDebugModeEnabled()) {
            return true;
        }

        $user = Auth::user();

        return $user && $user->isAdmin() && $user->getPreference('showExceptionView');
    }
}
