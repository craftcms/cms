<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Gql;

use craft\base\Event as YiiEvent;
use craft\events\RegisterGqlArgumentHandlersEvent;
use craft\gql\ArgumentManager as LegacyArgumentManager;
use CraftCms\Cms\Gql\GqlArguments;
use Illuminate\Support\Collection;
use Override;

/** @internal */
class LegacyGqlArguments extends GqlArguments
{
    #[Override]
    public function handlers(): Collection
    {
        $handlers = parent::handlers();

        if (!YiiEvent::hasHandlers(LegacyArgumentManager::class, LegacyArgumentManager::EVENT_DEFINE_GQL_ARGUMENT_HANDLERS)) {
            return $handlers;
        }

        $event = new RegisterGqlArgumentHandlersEvent(['handlers' => $handlers->all()]);
        YiiEvent::trigger(LegacyArgumentManager::class, LegacyArgumentManager::EVENT_DEFINE_GQL_ARGUMENT_HANDLERS, $event);

        return collect($event->handlers);
    }
}
