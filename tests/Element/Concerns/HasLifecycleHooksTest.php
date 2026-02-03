<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Events\AfterDelete;
use CraftCms\Cms\Element\Events\AfterPropagate;
use CraftCms\Cms\Element\Events\AfterRestore;
use CraftCms\Cms\Element\Events\AfterSave;
use CraftCms\Cms\Element\Events\BeforeDelete;
use CraftCms\Cms\Element\Events\BeforeRestore;
use CraftCms\Cms\Element\Events\BeforeSave;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;

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
    $triggered = false;
    Event::listen(function (AfterPropagate $event) use (&$triggered) {
        $triggered = true;
    });

    $this->entry->afterPropagate(false);

    expect($triggered)->toBeTrue();
});

test('afterPropagate event receives isNew parameter', function () {
    $receivedIsNew = null;
    Event::listen(function (AfterPropagate $event) use (&$receivedIsNew) {
        $receivedIsNew = $event->isNew;
    });

    $this->entry->afterPropagate(false);

    expect($receivedIsNew)->toBeFalse();
});

test('beforeDelete triggers event', function () {
    $triggered = false;
    Event::listen(function (BeforeDelete $event) use (&$triggered) {
        $triggered = true;
    });

    $this->entry->beforeDelete();

    expect($triggered)->toBeTrue();
});

test('beforeDelete event can prevent delete', function () {
    Event::listen(function (BeforeDelete $event) {
        $event->isValid = false;
    });

    $result = $this->entry->beforeDelete();

    expect($result)->toBeFalse();
});

test('afterDelete triggers event', function () {
    $triggered = false;
    Event::listen(function (AfterDelete $event) use (&$triggered) {
        $triggered = true;
    });

    $this->entry->afterDelete();

    expect($triggered)->toBeTrue();
});

test('beforeRestore triggers event', function () {
    $triggered = false;
    Event::listen(function (BeforeRestore $event) use (&$triggered) {
        $triggered = true;
    });

    $this->entry->beforeRestore();

    expect($triggered)->toBeTrue();
});

test('beforeRestore event can prevent restore', function () {
    Event::listen(function (BeforeRestore $event) {
        $event->isValid = false;
    });

    $result = $this->entry->beforeRestore();

    expect($result)->toBeFalse();
});

test('afterRestore triggers event', function () {
    $triggered = false;
    Event::listen(function (AfterRestore $event) use (&$triggered) {
        $triggered = true;
    });

    $this->entry->afterRestore();

    expect($triggered)->toBeTrue();
});
