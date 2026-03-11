<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Event;

use craft\base\Event as YiiEvent;
use craft\events\DefineEntryTypesForFieldEvent;
use craft\events\DefineInputOptionsEvent;
use craft\events\ElementCriteriaEvent;
use craft\events\LocateUploadedFilesEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\fields\Link;
use CraftCms\Cms\Element\Events\DefineElementCriteria;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\BaseOptionsField;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Events\DefineEntryTypesForField;
use CraftCms\Cms\Field\Events\DefineInputOptions;
use CraftCms\Cms\Field\Events\LocateUploadedFiles;
use CraftCms\Cms\Field\Events\RegisterLinkTypes;
use CraftCms\Cms\Field\Matrix;
use Illuminate\Support\Facades\Event;

final readonly class FieldEvents
{
    public static function registerEvents(): void
    {
        Assets::listen(Assets::EVENT_LOCATE_UPLOADED_FILES, function(LocateUploadedFiles $event) {
            $yiiEvent = new LocateUploadedFilesEvent([
                'element' => $event->element,
                'files' => $event->files,
            ]);
            $yiiEvent->sender = $event->field;
            YiiEvent::trigger(\craft\fields\Assets::class, Assets::EVENT_LOCATE_UPLOADED_FILES, $yiiEvent);
            $event->files = $yiiEvent->files;
        });

        BaseOptionsField::listen(BaseOptionsField::EVENT_DEFINE_OPTIONS, function(DefineInputOptions $event) {
            $yiiEvent = new DefineInputOptionsEvent([
                'options' => $event->options,
                'value' => $event->value,
                'element' => $event->element,
            ]);
            $yiiEvent->sender = $event->field;
            YiiEvent::trigger(\craft\fields\BaseOptionsField::class, BaseOptionsField::EVENT_DEFINE_OPTIONS, $yiiEvent);
            $event->options = $yiiEvent->options;
        });

        BaseRelationField::listen(BaseRelationField::EVENT_DEFINE_SELECTION_CRITERIA, function(DefineElementCriteria $event) {
            $yiiEvent = new ElementCriteriaEvent();
            YiiEvent::trigger(\craft\fields\BaseRelationField::class, BaseRelationField::EVENT_DEFINE_SELECTION_CRITERIA, $yiiEvent);
            $event->criteria = $yiiEvent->criteria;
        });

        // Link field
        Event::listen(function(RegisterLinkTypes $event) {
            $yiiEvent = new RegisterComponentTypesEvent([
                'types' => $event->types,
            ]);

            YiiEvent::trigger(Link::class, 'registerLinkTypes', $yiiEvent);

            $event->types = $yiiEvent->types;
        });

        // Matrix
        Matrix::listen(Matrix::EVENT_DEFINE_ENTRY_TYPES, function(DefineEntryTypesForField $event) {
            $yiiEvent = new DefineEntryTypesForFieldEvent([
                'entryTypes' => $event->entryTypes,
                'element' => $event->element,
                'value' => $event->value,
            ]);
            $yiiEvent->sender = $event->field;
            YiiEvent::trigger(\craft\fields\Matrix::class, Matrix::EVENT_DEFINE_ENTRY_TYPES, $yiiEvent);
            $event->entryTypes = $yiiEvent->entryTypes;
        });
    }
}
