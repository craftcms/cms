<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Gql;

use Craft;
use craft\events\RegisterGqlTypesEvent;
use craft\services\Gql as LegacyGqlService;
use CraftCms\Cms\Gql\GqlTypes;
use Illuminate\Support\Collection;
use Override;

/** @internal */
class LegacyGqlTypes extends GqlTypes
{
    #[Override]
    public function types(): Collection
    {
        $types = parent::types();
        $service = Craft::$app->getGql();

        if (!$service->hasEventHandlers(LegacyGqlService::EVENT_REGISTER_GQL_TYPES)) {
            return $types;
        }

        $event = new RegisterGqlTypesEvent(['types' => $types->all()]);
        $service->trigger(LegacyGqlService::EVENT_REGISTER_GQL_TYPES, $event);

        return new Collection($event->types);
    }
}
