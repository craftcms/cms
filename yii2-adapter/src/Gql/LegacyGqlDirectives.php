<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Gql;

use Craft;
use craft\events\RegisterGqlDirectivesEvent;
use craft\services\Gql as LegacyGqlService;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\GqlDirectives;
use Illuminate\Support\Collection;
use Override;

/** @internal */
class LegacyGqlDirectives extends GqlDirectives
{
    #[Override]
    public function forSchema(?GqlSchema $schema): Collection
    {
        $directives = parent::forSchema($schema);
        $service = Craft::$app->getGql();

        if (!$service->hasEventHandlers(LegacyGqlService::EVENT_REGISTER_GQL_DIRECTIVES)) {
            return $directives;
        }

        $event = new RegisterGqlDirectivesEvent(['directives' => $directives->all()]);
        $service->trigger(LegacyGqlService::EVENT_REGISTER_GQL_DIRECTIVES, $event);

        return collect($event->directives)->values();
    }
}
