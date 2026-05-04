<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\Events\AfterFieldDelete;
use CraftCms\Cms\Field\Events\AfterFieldElementDelete;
use CraftCms\Cms\Field\Events\AfterFieldElementPropagate;
use CraftCms\Cms\Field\Events\AfterFieldElementRestore;
use CraftCms\Cms\Field\Events\AfterFieldElementSave;
use CraftCms\Cms\Field\Events\AfterFieldMergeFrom;
use CraftCms\Cms\Field\Events\AfterFieldMergeInto;
use CraftCms\Cms\Field\Events\AfterFieldSave;
use CraftCms\Cms\Field\Events\BeforeApplyFieldDelete;
use CraftCms\Cms\Field\Events\BeforeFieldDelete;
use CraftCms\Cms\Field\Events\BeforeFieldElementDelete;
use CraftCms\Cms\Field\Events\BeforeFieldElementRestore;
use CraftCms\Cms\Field\Events\BeforeFieldElementSave;
use CraftCms\Cms\Field\Events\BeforeFieldSave;
use CraftCms\Cms\Field\Events\DefineFieldHtml;
use CraftCms\Cms\Field\Events\DefineFieldKeywords;
use CraftCms\Cms\Field\Events\DefineInputOptions;
use CraftCms\Cms\Field\PlainText;
use Illuminate\Support\Facades\Event;

class TestOptionsEventField extends Dropdown
{
    public function optionsForTest(): array
    {
        return $this->translatedOptions();
    }
}

class TestFieldEventElement extends Element
{
    #[Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }
}

it('can mutate and cancel field save and delete lifecycle events', function () {
    $field = new PlainText;
    $events = [];

    Event::listen(function (BeforeFieldSave $event) use ($field, &$events) {
        expect($event->field)->toBe($field);
        expect($event->isNew)->toBeTrue();

        $events[] = BeforeFieldSave::class;
        $event->isValid = false;
    });

    Event::listen(function (AfterFieldSave $event) use (&$events) {
        expect($event->isNew)->toBeFalse();
        $events[] = AfterFieldSave::class;
    });

    Event::listen(function (BeforeFieldDelete $event) use (&$events) {
        expect($event->field)->toBeInstanceOf(PlainText::class);
        $events[] = BeforeFieldDelete::class;
        $event->isValid = false;
    });

    Event::listen(function (BeforeApplyFieldDelete $event) use (&$events) {
        $events[] = BeforeApplyFieldDelete::class;
    });

    Event::listen(function (AfterFieldDelete $event) use (&$events) {
        $events[] = AfterFieldDelete::class;
    });

    expect($field->beforeSave(true))->toBeFalse();

    $field->afterSave(false);

    expect($field->beforeDelete())->toBeFalse();

    $field->beforeApplyDelete();
    $field->afterDelete();

    expect($events)->toBe([
        BeforeFieldSave::class,
        AfterFieldSave::class,
        BeforeFieldDelete::class,
        BeforeApplyFieldDelete::class,
        AfterFieldDelete::class,
    ]);
});

it('can mutate field rendering option and keyword events', function () {
    $plainText = new PlainText;
    $plainText->handle = 'body';

    Event::listen(function (DefineFieldHtml $event) use ($plainText) {
        expect($event->field)->toBe($plainText);
        expect($event->value)->toBe('Original');

        $event->html = '<input name="custom">';
    });

    expect($plainText->getInputHtml('Original', null))->toBe('<input name="custom">');

    $optionsField = new TestOptionsEventField;
    $optionsField->options = [
        ['label' => 'Original', 'value' => 'original'],
    ];

    Event::listen(function (DefineInputOptions $event) use ($optionsField) {
        expect($event->field)->toBe($optionsField);

        $event->options[] = ['label' => 'Extra', 'value' => 'extra'];
    });

    expect($optionsField->optionsForTest())->toHaveCount(2);

    $element = new TestFieldEventElement;

    Event::listen(function (DefineFieldKeywords $event) use ($plainText, $element) {
        expect($event->field)->toBe($plainText);
        expect($event->element)->toBe($element);

        $event->keywords = 'custom keywords';
        $event->handled = true;
    });

    expect($plainText->getSearchKeywords('ignored', $element))->toBe('custom keywords');
});

it('can mutate and cancel field element lifecycle events', function () {
    $field = new PlainText;
    $element = new TestFieldEventElement;
    $events = [];

    Event::listen(function (BeforeFieldElementSave $event) use ($field, $element, &$events) {
        expect($event->field)->toBe($field);
        expect($event->element)->toBe($element);
        expect($event->isNew)->toBeTrue();

        $events[] = BeforeFieldElementSave::class;
        $event->isValid = false;
    });

    Event::listen(function (AfterFieldElementSave $event) use (&$events) {
        $events[] = AfterFieldElementSave::class;
    });

    Event::listen(function (AfterFieldElementPropagate $event) use (&$events) {
        $events[] = AfterFieldElementPropagate::class;
    });

    Event::listen(function (BeforeFieldElementDelete $event) use (&$events) {
        $events[] = BeforeFieldElementDelete::class;
        $event->isValid = false;
    });

    Event::listen(function (AfterFieldElementDelete $event) use (&$events) {
        $events[] = AfterFieldElementDelete::class;
    });

    Event::listen(function (BeforeFieldElementRestore $event) use (&$events) {
        $events[] = BeforeFieldElementRestore::class;
        $event->isValid = false;
    });

    Event::listen(function (AfterFieldElementRestore $event) use (&$events) {
        $events[] = AfterFieldElementRestore::class;
    });

    expect($field->beforeElementSave($element, true))->toBeFalse();

    $field->afterElementSave($element, false);
    $field->afterElementPropagate($element, false);

    expect($field->beforeElementDelete($element))->toBeFalse();

    $field->afterElementDelete($element);

    expect($field->beforeElementRestore($element))->toBeFalse();

    $field->afterElementRestore($element);

    expect($events)->toBe([
        BeforeFieldElementSave::class,
        AfterFieldElementSave::class,
        AfterFieldElementPropagate::class,
        BeforeFieldElementDelete::class,
        AfterFieldElementDelete::class,
        BeforeFieldElementRestore::class,
        AfterFieldElementRestore::class,
    ]);
});

it('can dispatch field merge events', function () {
    $outgoingField = new PlainText;
    $persistingField = new PlainText;
    $events = [];

    Event::listen(function (AfterFieldMergeInto $event) use ($outgoingField, $persistingField, &$events) {
        expect($event->field)->toBe($outgoingField);
        expect($event->persistingField)->toBe($persistingField);

        $events[] = AfterFieldMergeInto::class;
    });

    Event::listen(function (AfterFieldMergeFrom $event) use ($outgoingField, $persistingField, &$events) {
        expect($event->field)->toBe($persistingField);
        expect($event->outgoingField)->toBe($outgoingField);

        $events[] = AfterFieldMergeFrom::class;
    });

    $outgoingField->afterMergeInto($persistingField);
    $persistingField->afterMergeFrom($outgoingField);

    expect($events)->toBe([
        AfterFieldMergeInto::class,
        AfterFieldMergeFrom::class,
    ]);
});
