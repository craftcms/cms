<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Gql;

use Craft;
use craft\events\RegisterGqlTypesEvent;
use craft\services\Gql as LegacyGqlService;
use CraftCms\Cms\Gql\Contracts\SingularTypeInterface;
use CraftCms\Cms\Gql\GqlTypes;
use Illuminate\Support\Collection;
use InvalidArgumentException;
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

        return collect($event->types)
            ->map(fn(mixed $type) => $this->normalizeType($type))
            ->values();
    }

    /** @return class-string<SingularTypeInterface> */
    private function normalizeType(mixed $type): string
    {
        if (is_string($type) && is_a($type, SingularTypeInterface::class, true)) {
            return $type;
        }

        throw new InvalidArgumentException('Legacy GraphQL types must implement ' . SingularTypeInterface::class . '.');
    }
}
