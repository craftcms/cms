<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Events\AfterSave;
use CraftCms\Cms\Element\Events\BeforeSave;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;
use yii\base\Event as YiiEvent;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
    $entryModel = EntryModel::factory()->create();
    $this->entry = Entry::findOne($entryModel->id);
});

test('beforeSave triggers event', function () {
    $triggered = false;
    Event::listen(function (BeforeSave $event) use (&$triggered) {
        $triggered = true;
    });

    $this->entry->beforeSave(false);

    expect($triggered)->toBeTrue();
});

test('beforeSave event can prevent save', function () {
    Event::listen(function (BeforeSave $event) {
        $event->isValid = false;
    });

    $result = $this->entry->beforeSave(false);

    expect($result)->toBeFalse();
});

test('beforeSave event receives isNew parameter', function () {
    $receivedIsNew = null;
    Event::listen(function (BeforeSave $event) use (&$receivedIsNew) {
        $receivedIsNew = $event->isNew;
    });

    $this->entry->beforeSave(true);

    expect($receivedIsNew)->toBeTrue();
});

test('afterSave triggers event', function () {
    $triggered = false;
    Event::listen(function (AfterSave $event) use (&$triggered) {
        $triggered = true;
    });

    $this->entry->afterSave(false);

    expect($triggered)->toBeTrue();
});

test('afterSave event receives isNew parameter', function () {
    $receivedIsNew = null;
    Event::listen(function (AfterSave $event) use (&$receivedIsNew) {
        $receivedIsNew = $event->isNew;
    });

    $this->entry->afterSave(false);

    expect($receivedIsNew)->toBeFalse();
});

test('afterPropagate triggers event', function () {
    expectYiiEvent(Entry::class, Element::EVENT_AFTER_PROPAGATE, fn () => $this->entry->afterPropagate(false));
});

test('beforeDelete triggers event', function () {
    expectYiiEvent(Entry::class, Element::EVENT_BEFORE_DELETE, fn () => $this->entry->beforeDelete());
});

test('afterDelete triggers event', function () {
    expectYiiEvent(Entry::class, Element::EVENT_AFTER_DELETE, fn () => $this->entry->afterDelete());
});

test('beforeRestore triggers event', function () {
    expectYiiEvent(Entry::class, Element::EVENT_BEFORE_RESTORE, fn () => $this->entry->beforeRestore());
});

test('afterRestore triggers event', function () {
    expectYiiEvent(Entry::class, Element::EVENT_AFTER_RESTORE, fn () => $this->entry->afterRestore());
});

function expectYiiEvent(string $class, string $eventName, callable $action): void
{
    $triggered = false;
    YiiEvent::on($class, $eventName, function () use (&$triggered) {
        $triggered = true;
    });

    $action();

    expect($triggered)->toBeTrue();
}
