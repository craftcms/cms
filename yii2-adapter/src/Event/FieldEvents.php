<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Event;

use craft\base\Event as YiiEvent;
use craft\events\ElementCriteriaEvent;
use CraftCms\Cms\Element\Events\DefineElementCriteria;
use CraftCms\Cms\Field\BaseRelationField;

readonly class FieldEvents
{
    public static function registerEvents(): void
    {
        BaseRelationField::listen(BaseRelationField::EVENT_DEFINE_SELECTION_CRITERIA, function(DefineElementCriteria $event) {
            $yiiEvent = new ElementCriteriaEvent();
            YiiEvent::trigger(\craft\fields\BaseRelationField::class, BaseRelationField::EVENT_DEFINE_SELECTION_CRITERIA, $yiiEvent);
            $event->criteria = $yiiEvent->criteria;
        });
    }
}
