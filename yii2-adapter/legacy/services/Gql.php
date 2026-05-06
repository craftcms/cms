<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\errors\GqlException;
use craft\events\DefineGqlValidationRulesEvent;
use craft\events\ExecuteGqlQueryEvent;
use craft\events\RegisterGqlDirectivesEvent;
use craft\events\RegisterGqlMutationsEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlSchemaComponentsEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\models\GqlSchema;
use craft\models\GqlToken;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Gql\Events\ExecutedGqlQuery;
use CraftCms\Cms\Gql\Events\GqlDirectivesResolving;
use CraftCms\Cms\Gql\Events\GqlMutationsResolving;
use CraftCms\Cms\Gql\Events\GqlQueriesResolving;
use CraftCms\Cms\Gql\Events\GqlQueryExecuting;
use CraftCms\Cms\Gql\Events\GqlSchemaComponentsResolving;
use CraftCms\Cms\Gql\Events\GqlTypesResolving;
use CraftCms\Cms\Gql\Events\GqlValidationRulesResolving;
use CraftCms\Cms\Gql\Gql as NewGql;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use GraphQL\Type\Schema;
use Illuminate\Support\Facades\Event;
use yii\base\Component;
use yii\base\Exception;

/**
 * GraphQL service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getGql()|`Craft::$app->getGql()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Gql\Gql} instead.
 */
class Gql extends Component
{
    public const EVENT_REGISTER_GQL_TYPES = 'registerGqlTypes';
    public const EVENT_REGISTER_GQL_QUERIES = 'registerGqlQueries';
    public const EVENT_REGISTER_GQL_MUTATIONS = 'registerGqlMutations';
    public const EVENT_REGISTER_GQL_DIRECTIVES = 'registerGqlDirectives';
    public const EVENT_REGISTER_GQL_SCHEMA_COMPONENTS = 'registerGqlSchemaComponents';
    public const EVENT_DEFINE_GQL_VALIDATION_RULES = 'defineGqlValidationRules';
    public const EVENT_BEFORE_EXECUTE_GQL_QUERY = 'beforeExecuteGqlQuery';
    public const EVENT_AFTER_EXECUTE_GQL_QUERY = 'afterExecuteGqlQuery';
    public const CACHE_TAG = NewGql::CACHE_TAG;
    public const GRAPHQL_COUNT_FIELD = NewGql::GRAPHQL_COUNT_FIELD;
    public const GRAPHQL_COMPLEXITY_SIMPLE_FIELD = NewGql::GRAPHQL_COMPLEXITY_SIMPLE_FIELD;
    public const GRAPHQL_COMPLEXITY_QUERY = NewGql::GRAPHQL_COMPLEXITY_QUERY;
    public const GRAPHQL_COMPLEXITY_EAGER_LOAD = NewGql::GRAPHQL_COMPLEXITY_EAGER_LOAD;
    public const GRAPHQL_COMPLEXITY_CPU_HEAVY = NewGql::GRAPHQL_COMPLEXITY_CPU_HEAVY;
    public const GRAPHQL_COMPLEXITY_NPLUS1 = NewGql::GRAPHQL_COMPLEXITY_NPLUS1;

    public function getSchemaDef(?GqlSchema $schema = null, bool $prebuildSchema = false): Schema
    {
        return app(NewGql::class)->getSchemaDef($schema, $prebuildSchema);
    }

    public function getValidationRules(bool $debug = false, bool $isIntrospectionQuery = false): array
    {
        return app(NewGql::class)->getValidationRules($debug, $isIntrospectionQuery);
    }

    public function executeQuery(
        GqlSchema $schema,
        string $query,
        ?array $variables = null,
        ?string $operationName = null,
        bool $debugMode = false,
    ): array {
        return app(NewGql::class)->executeQuery($schema, $query, $variables, $operationName, $debugMode);
    }

    public function invalidateCaches(): void
    {
        app(NewGql::class)->invalidateCaches();
    }

    public function getCachedResult(string $cacheKey): ?array
    {
        return app(NewGql::class)->getCachedResult($cacheKey);
    }

    public function setCachedResult(
        string $cacheKey,
        array $result,
        ?TagDependency $dependency = null,
        ?int $duration = null,
    ): void {
        app(NewGql::class)->setCachedResult($cacheKey, $result, $dependency, $duration);
    }

    /**
     * @throws GqlException
     */
    public function getActiveSchema(): GqlSchema
    {
        return app(NewGql::class)->getActiveSchema();
    }

    public function setActiveSchema(?GqlSchema $schema = null): void
    {
        app(NewGql::class)->setActiveSchema($schema);
    }

    public function getTokens(): array
    {
        return app(NewGql::class)->getTokens();
    }

    /**
     * @throws Exception
     */
    public function getPublicSchema(): ?GqlSchema
    {
        return app(NewGql::class)->getPublicSchema();
    }

    public function getAllSchemaComponents(): array
    {
        return app(NewGql::class)->getAllSchemaComponents();
    }

    public function flushCaches(): void
    {
        app(NewGql::class)->flushCaches();
    }

    public function getTokenById(int $id): ?GqlToken
    {
        return app(NewGql::class)->getTokenById($id);
    }

    public function getTokenByName(string $tokenName): ?GqlToken
    {
        return app(NewGql::class)->getTokenByName($tokenName);
    }

    public function getTokenByUid(string $uid): GqlToken
    {
        return app(NewGql::class)->getTokenByUid($uid);
    }

    public function getTokenByAccessToken(string $token): GqlToken
    {
        return app(NewGql::class)->getTokenByAccessToken($token);
    }

    public function getPublicToken(): ?GqlToken
    {
        return app(NewGql::class)->getPublicToken();
    }

    /**
     * @throws Exception
     */
    public function saveToken(GqlToken $token, bool $runValidation = true): bool
    {
        return app(NewGql::class)->saveToken($token, $runValidation);
    }

    public function handleChangedPublicToken(ConfigEvent $event): void
    {
        app(NewGql::class)->handleChangedPublicToken($event);
    }

    public function deleteTokenById(int $id): bool
    {
        return app(NewGql::class)->deleteTokenById($id);
    }

    /**
     * @throws Exception
     */
    public function saveSchema(GqlSchema $schema, bool $runValidation = true): bool
    {
        return app(NewGql::class)->saveSchema($schema, $runValidation);
    }

    public function handleChangedSchema(ConfigEvent $event): void
    {
        app(NewGql::class)->handleChangedSchema($event);
    }

    public function deleteSchemaById(int $id): bool
    {
        return app(NewGql::class)->deleteSchemaById($id);
    }

    public function deleteSchema(GqlSchema $schema): bool
    {
        return app(NewGql::class)->deleteSchema($schema);
    }

    public function handleDeletedSchema(ConfigEvent $event): void
    {
        app(NewGql::class)->handleDeletedSchema($event);
    }

    public function getSchemaById(int $id): ?GqlSchema
    {
        return app(NewGql::class)->getSchemaById($id);
    }

    public function getSchemaByUid(string $uid): ?GqlSchema
    {
        return app(NewGql::class)->getSchemaByUid($uid);
    }

    public function getSchemas(): array
    {
        return app(NewGql::class)->getSchemas();
    }

    public function getOrSetContentArguments(string $elementType, callable $setter): array
    {
        return app(NewGql::class)->getOrSetContentArguments($elementType, $setter);
    }

    public function getFieldLayoutArguments(FieldLayout $fieldLayout): array
    {
        return app(NewGql::class)->getFieldLayoutArguments($fieldLayout);
    }

    public function defineContentArgumentsForFieldLayouts(string $elementType, array $fieldLayouts): array
    {
        return app(NewGql::class)->defineContentArgumentsForFieldLayouts($elementType, $fieldLayouts);
    }

    public function defineContentArgumentsForFields(string $elementType, array $fields): array
    {
        return app(NewGql::class)->defineContentArgumentsForFields($elementType, $fields);
    }

    public function defineContentArgumentsForGeneratedFields(string $elementType, array $fields): array
    {
        return app(NewGql::class)->defineContentArgumentsForGeneratedFields($elementType, $fields);
    }

    public function getContentArguments(array $contexts, string $elementType): array
    {
        return app(NewGql::class)->getContentArguments($contexts, $elementType);
    }

    public function handleQueryErrors(array $errors, callable $formatter): array
    {
        return app(NewGql::class)->handleQueryErrors($errors, $formatter);
    }

    public function prepareFieldDefinitions(array $fields, string $typeName): array
    {
        return app(NewGql::class)->prepareFieldDefinitions($fields, $typeName);
    }

    public static function registerEvents(): void
    {
        Event::listen(GqlTypesResolving::class, function(GqlTypesResolving $event) {
            $service = self::service();
            if (!$service->hasEventHandlers(self::EVENT_REGISTER_GQL_TYPES)) {
                return;
            }

            $yiiEvent = new RegisterGqlTypesEvent(['types' => $event->types]);
            $service->trigger(self::EVENT_REGISTER_GQL_TYPES, $yiiEvent);
            $event->types = $yiiEvent->types;
        });

        Event::listen(GqlQueriesResolving::class, function(GqlQueriesResolving $event) {
            $service = self::service();
            if (!$service->hasEventHandlers(self::EVENT_REGISTER_GQL_QUERIES)) {
                return;
            }

            $yiiEvent = new RegisterGqlQueriesEvent(['queries' => $event->queries]);
            $service->trigger(self::EVENT_REGISTER_GQL_QUERIES, $yiiEvent);
            $event->queries = $yiiEvent->queries;
        });

        Event::listen(GqlMutationsResolving::class, function(GqlMutationsResolving $event) {
            $service = self::service();
            if (!$service->hasEventHandlers(self::EVENT_REGISTER_GQL_MUTATIONS)) {
                return;
            }

            $yiiEvent = new RegisterGqlMutationsEvent(['mutations' => $event->mutations]);
            $service->trigger(self::EVENT_REGISTER_GQL_MUTATIONS, $yiiEvent);
            $event->mutations = $yiiEvent->mutations;
        });

        Event::listen(GqlDirectivesResolving::class, function(GqlDirectivesResolving $event) {
            $service = self::service();
            if (!$service->hasEventHandlers(self::EVENT_REGISTER_GQL_DIRECTIVES)) {
                return;
            }

            $yiiEvent = new RegisterGqlDirectivesEvent(['directives' => $event->directives]);
            $service->trigger(self::EVENT_REGISTER_GQL_DIRECTIVES, $yiiEvent);
            $event->directives = $yiiEvent->directives;
        });

        Event::listen(GqlSchemaComponentsResolving::class, function(GqlSchemaComponentsResolving $event) {
            $service = self::service();
            if (!$service->hasEventHandlers(self::EVENT_REGISTER_GQL_SCHEMA_COMPONENTS)) {
                return;
            }

            $yiiEvent = new RegisterGqlSchemaComponentsEvent([
                'queries' => $event->queries,
                'mutations' => $event->mutations,
            ]);
            $service->trigger(self::EVENT_REGISTER_GQL_SCHEMA_COMPONENTS, $yiiEvent);
            $event->queries = $yiiEvent->queries;
            $event->mutations = $yiiEvent->mutations;
        });

        Event::listen(GqlValidationRulesResolving::class, function(GqlValidationRulesResolving $event) {
            $service = self::service();
            if (!$service->hasEventHandlers(self::EVENT_DEFINE_GQL_VALIDATION_RULES)) {
                return;
            }

            $yiiEvent = new DefineGqlValidationRulesEvent([
                'validationRules' => $event->validationRules,
                'debug' => $event->debug,
            ]);
            $service->trigger(self::EVENT_DEFINE_GQL_VALIDATION_RULES, $yiiEvent);
            $event->validationRules = $yiiEvent->validationRules;
        });

        Event::listen(GqlQueryExecuting::class, function(GqlQueryExecuting $event) {
            $service = self::service();
            if (!$service->hasEventHandlers(self::EVENT_BEFORE_EXECUTE_GQL_QUERY)) {
                return;
            }

            $yiiEvent = new ExecuteGqlQueryEvent([
                'schemaId' => $event->schema->id,
                'query' => $event->query,
                'variables' => $event->variables,
                'operationName' => $event->operationName,
                'context' => $event->context,
                'rootValue' => $event->rootValue,
                'result' => $event->result,
            ]);
            $service->trigger(self::EVENT_BEFORE_EXECUTE_GQL_QUERY, $yiiEvent);
            $event->query = $yiiEvent->query;
            $event->variables = $yiiEvent->variables;
            $event->operationName = $yiiEvent->operationName;
            $event->context = $yiiEvent->context;
            $event->rootValue = $yiiEvent->rootValue;
            $event->result = $yiiEvent->result;
        });

        Event::listen(ExecutedGqlQuery::class, function(ExecutedGqlQuery $event) {
            $service = self::service();
            if (!$service->hasEventHandlers(self::EVENT_AFTER_EXECUTE_GQL_QUERY)) {
                return;
            }

            $yiiEvent = new ExecuteGqlQueryEvent([
                'schemaId' => $event->schema->id,
                'query' => $event->query,
                'variables' => $event->variables,
                'operationName' => $event->operationName,
                'context' => $event->context,
                'rootValue' => $event->rootValue,
                'result' => $event->result,
                'cacheTags' => $event->cacheTags,
                'cacheDuration' => $event->cacheDuration,
            ]);
            $service->trigger(self::EVENT_AFTER_EXECUTE_GQL_QUERY, $yiiEvent);
            $event->query = $yiiEvent->query;
            $event->variables = $yiiEvent->variables;
            $event->operationName = $yiiEvent->operationName;
            $event->context = $yiiEvent->context;
            $event->rootValue = $yiiEvent->rootValue;
            $event->result = $yiiEvent->result;
            $event->cacheTags = $yiiEvent->cacheTags;
            $event->cacheDuration = $yiiEvent->cacheDuration;
        });
    }

    private static function service(): self
    {
        return Craft::$app->getGql();
    }
}
