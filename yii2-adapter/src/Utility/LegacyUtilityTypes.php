<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Utility;

use Craft;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Utilities as LegacyUtilities;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Utility\Utility;
use CraftCms\Cms\Utility\UtilityTypes;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Override;

/** @internal */
class LegacyUtilityTypes extends UtilityTypes
{
    #[Override]
    public function types(): Collection
    {
        $types = parent::types();
        $service = Craft::$app->getUtilities();

        if (!$service->hasEventHandlers(LegacyUtilities::EVENT_REGISTER_UTILITIES)) {
            return $types;
        }

        $event = new RegisterComponentTypesEvent(['types' => $types->all()]);
        $service->trigger(LegacyUtilities::EVENT_REGISTER_UTILITIES, $event);
        $disabledUtilities = array_flip(Cms::config()->disabledUtilities);

        return collect($event->types)
            ->map(fn(mixed $type) => $this->normalizeType($type))
            ->filter(fn(string $type) => !isset($disabledUtilities[$type::id()]) && $type::isSelectable())
            ->values();
    }

    /** @return class-string<Utility> */
    private function normalizeType(mixed $type): string
    {
        if (is_string($type) && is_a($type, Utility::class, true)) {
            return $type;
        }

        throw new InvalidArgumentException('Legacy utility types must extend ' . Utility::class . '.');
    }
}
