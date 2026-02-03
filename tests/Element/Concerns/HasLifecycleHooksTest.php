<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User;
use yii\base\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
    $entryModel = EntryModel::factory()->create();
    $this->entry = Entry::findOne($entryModel->id);
});

test('beforeSave triggers event', function () {
    expectEvent(Entry::class, Element::EVENT_BEFORE_SAVE, fn () => $this->entry->beforeSave(false));
});

test('afterSave triggers event', function () {
    expectEvent(Entry::class, Element::EVENT_AFTER_SAVE, fn () => $this->entry->afterSave(false));
});

test('afterPropagate triggers event', function () {
    expectEvent(Entry::class, Element::EVENT_AFTER_PROPAGATE, fn () => $this->entry->afterPropagate(false));
});

test('beforeDelete triggers event', function () {
    expectEvent(Entry::class, Element::EVENT_BEFORE_DELETE, fn () => $this->entry->beforeDelete());
});

test('afterDelete triggers event', function () {
    expectEvent(Entry::class, Element::EVENT_AFTER_DELETE, fn () => $this->entry->afterDelete());
});

test('beforeRestore triggers event', function () {
    expectEvent(Entry::class, Element::EVENT_BEFORE_RESTORE, fn () => $this->entry->beforeRestore());
});

test('afterRestore triggers event', function () {
    expectEvent(Entry::class, Element::EVENT_AFTER_RESTORE, fn () => $this->entry->afterRestore());
});

function expectEvent(string $class, string $eventName, callable $action): void
{
    $triggered = false;
    Event::on($class, $eventName, function () use (&$triggered) {
        $triggered = true;
    });

    $action();

    expect($triggered)->toBeTrue();
}
