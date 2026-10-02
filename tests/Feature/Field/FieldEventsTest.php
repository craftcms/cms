<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\Events\FieldDeletionApplying;
use CraftCms\Cms\Field\Events\FieldElementDeleted;
use CraftCms\Cms\Field\Events\FieldElementDeleting;
use CraftCms\Cms\Field\Events\FieldElementPropagated;
use CraftCms\Cms\Field\Events\FieldElementRestored;
use CraftCms\Cms\Field\Events\FieldElementRestoring;
use CraftCms\Cms\Field\Events\FieldElementSaved;
use CraftCms\Cms\Field\Events\FieldElementSaving;
use CraftCms\Cms\Field\Events\FieldHtmlResolving;
use CraftCms\Cms\Field\Events\FieldKeywordsResolving;
use CraftCms\Cms\Field\Events\FieldLifecycleDeleted;
use CraftCms\Cms\Field\Events\FieldLifecycleDeleting;
use CraftCms\Cms\Field\Events\FieldLifecycleSaved;
use CraftCms\Cms\Field\Events\FieldLifecycleSaving;
use CraftCms\Cms\Field\Events\FieldMergeFromCompleted;
use CraftCms\Cms\Field\Events\FieldMergeIntoCompleted;
use CraftCms\Cms\Field\Events\InputOptionsResolving;
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

    Event::listen(function (FieldLifecycleSaving $event) use ($field, &$events) {
        expect($event->field)->toBe($field);
        expect($event->isNew)->toBeTrue();

        $events[] = FieldLifecycleSaving::class;
        $event->isValid = false;
    });

    Event::listen(function (FieldLifecycleSaved $event) use (&$events) {
        expect($event->isNew)->toBeFalse();
        $events[] = FieldLifecycleSaved::class;
    });

    Event::listen(function (FieldLifecycleDeleting $event) use (&$events) {
        expect($event->field)->toBeInstanceOf(PlainText::class);
        $events[] = FieldLifecycleDeleting::class;
        $event->isValid = false;
    });

    Event::listen(function (FieldDeletionApplying $event) use (&$events) {
        $events[] = FieldDeletionApplying::class;
    });

    Event::listen(function (FieldLifecycleDeleted $event) use (&$events) {
        $events[] = FieldLifecycleDeleted::class;
    });

    expect($field->beforeSave(true))->toBeFalse();

    $field->afterSave(false);

    expect($field->beforeDelete())->toBeFalse();

    $field->beforeApplyDelete();
    $field->afterDelete();

    expect($events)->toBe([
        FieldLifecycleSaving::class,
        FieldLifecycleSaved::class,
        FieldLifecycleDeleting::class,
        FieldDeletionApplying::class,
        FieldLifecycleDeleted::class,
    ]);
});

it('can mutate field rendering option and keyword events', function () {
    $plainText = new PlainText;
    $plainText->handle = 'body';

    Event::listen(function (FieldHtmlResolving $event) use ($plainText) {
        expect($event->field)->toBe($plainText);
        expect($event->value)->toBe('Original');

        $event->html = '<input name="custom">';
    });

    expect($plainText->getInlineInputHtml('Original', null))->toBe('<input name="custom">');

    $optionsField = new TestOptionsEventField;
    $optionsField->options = [
        ['label' => 'Original', 'value' => 'original'],
    ];

    Event::listen(function (InputOptionsResolving $event) use ($optionsField) {
        expect($event->field)->toBe($optionsField);

        $event->options[] = ['label' => 'Extra', 'value' => 'extra'];
    });

    expect($optionsField->optionsForTest())->toHaveCount(2);

    $element = new TestFieldEventElement;

    Event::listen(function (FieldKeywordsResolving $event) use ($plainText, $element) {
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

    Event::listen(function (FieldElementSaving $event) use ($field, $element, &$events) {
        expect($event->field)->toBe($field);
        expect($event->element)->toBe($element);
        expect($event->isNew)->toBeTrue();

        $events[] = FieldElementSaving::class;
        $event->isValid = false;
    });

    Event::listen(function (FieldElementSaved $event) use (&$events) {
        $events[] = FieldElementSaved::class;
    });

    Event::listen(function (FieldElementPropagated $event) use (&$events) {
        $events[] = FieldElementPropagated::class;
    });

    Event::listen(function (FieldElementDeleting $event) use (&$events) {
        $events[] = FieldElementDeleting::class;
        $event->isValid = false;
    });

    Event::listen(function (FieldElementDeleted $event) use (&$events) {
        $events[] = FieldElementDeleted::class;
    });

    Event::listen(function (FieldElementRestoring $event) use (&$events) {
        $events[] = FieldElementRestoring::class;
        $event->isValid = false;
    });

    Event::listen(function (FieldElementRestored $event) use (&$events) {
        $events[] = FieldElementRestored::class;
    });

    expect($field->beforeElementSave($element, true))->toBeFalse();

    $field->afterElementSave($element, false);
    $field->afterElementPropagate($element, false);

    expect($field->beforeElementDelete($element))->toBeFalse();

    $field->afterElementDelete($element);

    expect($field->beforeElementRestore($element))->toBeFalse();

    $field->afterElementRestore($element);

    expect($events)->toBe([
        FieldElementSaving::class,
        FieldElementSaved::class,
        FieldElementPropagated::class,
        FieldElementDeleting::class,
        FieldElementDeleted::class,
        FieldElementRestoring::class,
        FieldElementRestored::class,
    ]);
});

it('can dispatch field merge events', function () {
    $outgoingField = new PlainText;
    $persistingField = new PlainText;
    $events = [];

    Event::listen(function (FieldMergeIntoCompleted $event) use ($outgoingField, $persistingField, &$events) {
        expect($event->field)->toBe($outgoingField);
        expect($event->persistingField)->toBe($persistingField);

        $events[] = FieldMergeIntoCompleted::class;
    });

    Event::listen(function (FieldMergeFromCompleted $event) use ($outgoingField, $persistingField, &$events) {
        expect($event->field)->toBe($persistingField);
        expect($event->outgoingField)->toBe($outgoingField);

        $events[] = FieldMergeFromCompleted::class;
    });

    $outgoingField->afterMergeInto($persistingField);
    $persistingField->afterMergeFrom($outgoingField);

    expect($events)->toBe([
        FieldMergeIntoCompleted::class,
        FieldMergeFromCompleted::class,
    ]);
});
