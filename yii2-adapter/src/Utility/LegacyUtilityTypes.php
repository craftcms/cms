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
            /** @var class-string<Utility> $type */
            ->filter(fn(string $type) => !isset($disabledUtilities[$type::id()]) && $type::isSelectable())
            ->values();
    }
}
