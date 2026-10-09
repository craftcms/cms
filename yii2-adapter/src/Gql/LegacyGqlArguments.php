<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Gql;

use Closure;
use craft\base\Event as YiiEvent;
use craft\events\RegisterGqlArgumentHandlersEvent;
use craft\gql\ArgumentManager as LegacyArgumentManager;
use CraftCms\Cms\Gql\Contracts\ArgumentHandlerInterface;
use CraftCms\Cms\Gql\GqlArguments;
use Illuminate\Support\Collection;
use InvalidArgumentException;
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

        return collect($event->handlers)
            ->map(fn(mixed $handler) => $this->normalizeHandler($handler));
    }

    /** @return class-string<ArgumentHandlerInterface>|Closure */
    private function normalizeHandler(mixed $handler): string|Closure
    {
        if ($handler instanceof Closure) {
            return $handler;
        }

        if ($handler instanceof ArgumentHandlerInterface) {
            return fn() => $handler;
        }

        if (is_string($handler) && is_a($handler, ArgumentHandlerInterface::class, true)) {
            return $handler;
        }

        throw new InvalidArgumentException('Legacy GraphQL argument handlers must implement ' . ArgumentHandlerInterface::class . '.');
    }
}
