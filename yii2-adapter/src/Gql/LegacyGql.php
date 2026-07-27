<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Gql;

use Craft;
use craft\events\RegisterGqlMutationsEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\services\Gql as LegacyGqlService;
use CraftCms\Cms\Gql\Gql;
use Override;

/** @internal */
class LegacyGql extends Gql
{
    #[Override]
    protected function queryDefinitions(): array
    {
        $queries = parent::queryDefinitions();
        $service = Craft::$app->getGql();

        if (!$service->hasEventHandlers(LegacyGqlService::EVENT_REGISTER_GQL_QUERIES)) {
            return $queries;
        }

        $event = new RegisterGqlQueriesEvent(['queries' => $queries]);
        $service->trigger(LegacyGqlService::EVENT_REGISTER_GQL_QUERIES, $event);

        return $event->queries;
    }

    #[Override]
    protected function mutationDefinitions(): array
    {
        $mutations = parent::mutationDefinitions();
        $service = Craft::$app->getGql();

        if (!$service->hasEventHandlers(LegacyGqlService::EVENT_REGISTER_GQL_MUTATIONS)) {
            return $mutations;
        }

        $event = new RegisterGqlMutationsEvent(['mutations' => $mutations]);
        $service->trigger(LegacyGqlService::EVENT_REGISTER_GQL_MUTATIONS, $event);

        return $event->mutations;
    }
}
