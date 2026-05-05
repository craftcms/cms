<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Event;

use craft\base\Event as YiiEvent;
use craft\events\ElementCriteriaEvent;
use craft\fields\BaseRelationField as LegacyBaseRelationField;
use CraftCms\Cms\Element\Events\ElementCriteriaResolving;
use CraftCms\Cms\Field\BaseRelationField;
use Illuminate\Support\Facades\Event;

readonly class FieldEvents
{
    public static function registerEvents(): void
    {
        Event::listen(function(ElementCriteriaResolving $event) {
            if (!$event->field instanceof BaseRelationField) {
                return;
            }

            if (!YiiEvent::hasHandlers(LegacyBaseRelationField::class, LegacyBaseRelationField::EVENT_DEFINE_SELECTION_CRITERIA)) {
                return;
            }

            $yiiEvent = new ElementCriteriaEvent();
            YiiEvent::trigger(LegacyBaseRelationField::class, LegacyBaseRelationField::EVENT_DEFINE_SELECTION_CRITERIA, $yiiEvent);
            $event->criteria = $yiiEvent->criteria;
        });
    }
}
