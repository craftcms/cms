<?php

declare(strict_types=1);

use craft\base\Event as YiiEvent;
use craft\base\Field as LegacyField;
use craft\events\DefineEntryTypesForFieldEvent;
use craft\events\DefineInputOptionsEvent;
use craft\events\ElementCriteriaEvent;
use craft\events\FieldEvent as YiiFieldEvent;
use craft\events\LocateUploadedFilesEvent;
use craft\events\ModelEvent;
use craft\fields\Assets as LegacyAssets;
use craft\fields\BaseOptionsField as LegacyBaseOptionsField;
use craft\fields\BaseRelationField as LegacyBaseRelationField;
use craft\fields\Entries as LegacyEntries;
use craft\fields\Matrix as LegacyMatrix;
use craft\fields\PlainText as LegacyPlainText;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\Events\AssetsUploadedFilesLocating;
use CraftCms\Cms\Field\Events\EntryTypesForFieldResolving;

class TestLegacyOptionsEventField extends Dropdown
{
    public function optionsForTest(): array
    {
        return $this->translatedOptions();
    }
}

afterEach(function() {
    foreach ([
        LegacyField::class => [
            LegacyField::EVENT_BEFORE_SAVE,
            LegacyField::EVENT_AFTER_SAVE,
            LegacyField::EVENT_BEFORE_DELETE,
            LegacyField::EVENT_AFTER_DELETE,
            LegacyField::EVENT_AFTER_MERGE_INTO,
        ],
        LegacyPlainText::class => [
            LegacyPlainText::EVENT_BEFORE_SAVE,
            LegacyPlainText::EVENT_AFTER_SAVE,
            LegacyPlainText::EVENT_BEFORE_DELETE,
            LegacyPlainText::EVENT_AFTER_DELETE,
            LegacyPlainText::EVENT_AFTER_MERGE_INTO,
        ],
        LegacyBaseRelationField::class => [LegacyBaseRelationField::EVENT_DEFINE_SELECTION_CRITERIA],
        LegacyBaseOptionsField::class => [LegacyBaseOptionsField::EVENT_DEFINE_OPTIONS],
        LegacyMatrix::class => [LegacyMatrix::EVENT_DEFINE_ENTRY_TYPES],
        LegacyAssets::class => [LegacyAssets::EVENT_LOCATE_UPLOADED_FILES],
    ] as $class => $events) {
        foreach ($events as $event) {
            YiiEvent::off($class, $event);
        }
    }
});

it('bridges legacy base relation selection criteria events', function() {
    YiiEvent::on(LegacyBaseRelationField::class, LegacyBaseRelationField::EVENT_DEFINE_SELECTION_CRITERIA, function(ElementCriteriaEvent $event) {
        $event->criteria = ['status' => 'enabled'];
    });

    $field = new LegacyEntries();

    expect($field->getInputSelectionCriteria())->toMatchArray(['status' => 'enabled']);
});

it('bridges legacy base options field events', function() {
    YiiEvent::on(LegacyBaseOptionsField::class, LegacyBaseOptionsField::EVENT_DEFINE_OPTIONS, function(DefineInputOptionsEvent $event) {
        expect($event->sender)->toBeInstanceOf(TestLegacyOptionsEventField::class);

        $event->options[] = ['label' => 'Extra', 'value' => 'extra'];
    });

    $field = new TestLegacyOptionsEventField();
    $field->options = [
        ['label' => 'Original', 'value' => 'original'],
    ];

    expect($field->optionsForTest())->toHaveCount(2);
});

it('bridges legacy matrix entry type events', function() {
    $field = new LegacyMatrix();
    $entryType = new EntryType();
    $entryType->handle = 'article';
    $event = new EntryTypesForFieldResolving(
        field: $field,
        entryTypes: [$entryType],
        element: null,
        value: [],
    );

    YiiEvent::on(LegacyMatrix::class, LegacyMatrix::EVENT_DEFINE_ENTRY_TYPES, function(DefineEntryTypesForFieldEvent $event) {
        $entryType = new EntryType();
        $entryType->handle = 'custom';

        $event->entryTypes = [$entryType];
    });

    event($event);

    expect($event->entryTypes)->toHaveCount(1)
        ->and($event->entryTypes[0]->handle)->toBe('custom');
});

it('bridges legacy asset upload location events', function() {
    $field = new LegacyAssets();
    $element = new class() extends \CraftCms\Cms\Element\Element {
        public static function displayName(): string
        {
            return 'Test Element';
        }
    };
    $event = new AssetsUploadedFilesLocating(
        field: $field,
        element: $element,
        files: [],
    );

    YiiEvent::on(LegacyAssets::class, LegacyAssets::EVENT_LOCATE_UPLOADED_FILES, function(LocateUploadedFilesEvent $event) {
        expect($event->sender)->toBeInstanceOf(LegacyAssets::class);

        $event->files[] = [
            'type' => 'data',
            'filename' => 'test.txt',
            'mimeType' => 'text/plain',
            'data' => 'test',
        ];
    });

    event($event);

    expect($event->files)->toHaveCount(1)
        ->and($event->files[0]['filename'])->toBe('test.txt');
});

it('bridges legacy field save and delete events', function() {
    $field = new LegacyPlainText();
    $events = [];

    YiiEvent::on(LegacyPlainText::class, LegacyPlainText::EVENT_BEFORE_SAVE, function(ModelEvent $event) use ($field, &$events) {
        expect($event->sender)->toBe($field);
        expect($event->isNew)->toBeTrue();

        $events[] = LegacyPlainText::EVENT_BEFORE_SAVE;
        $event->isValid = false;
    });

    YiiEvent::on(LegacyPlainText::class, LegacyPlainText::EVENT_AFTER_SAVE, function(ModelEvent $event) use (&$events) {
        expect($event->isNew)->toBeFalse();
        $events[] = LegacyPlainText::EVENT_AFTER_SAVE;
    });

    YiiEvent::on(LegacyPlainText::class, LegacyPlainText::EVENT_BEFORE_DELETE, function(ModelEvent $event) use (&$events) {
        $events[] = LegacyPlainText::EVENT_BEFORE_DELETE;
        $event->isValid = false;
    });

    YiiEvent::on(LegacyPlainText::class, LegacyPlainText::EVENT_AFTER_DELETE, function(YiiEvent $event) use (&$events) {
        $events[] = LegacyPlainText::EVENT_AFTER_DELETE;
    });

    YiiEvent::on(LegacyPlainText::class, LegacyPlainText::EVENT_AFTER_MERGE_INTO, function(YiiFieldEvent $event) use (&$events) {
        expect($event->field)->toBeInstanceOf(LegacyPlainText::class);
        $events[] = LegacyPlainText::EVENT_AFTER_MERGE_INTO;
    });

    expect($field->beforeSave(true))->toBeFalse();

    $field->afterSave(false);

    expect($field->beforeDelete())->toBeFalse();

    $field->afterDelete();
    $field->afterMergeInto(new LegacyPlainText());

    expect($events)->toBe([
        LegacyPlainText::EVENT_BEFORE_SAVE,
        LegacyPlainText::EVENT_AFTER_SAVE,
        LegacyPlainText::EVENT_BEFORE_DELETE,
        LegacyPlainText::EVENT_AFTER_DELETE,
        LegacyPlainText::EVENT_AFTER_MERGE_INTO,
    ]);
});
