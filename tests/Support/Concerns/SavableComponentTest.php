<?php

declare(strict_types=1);

use CraftCms\Cms\Component\Events\ComponentEvent;
use CraftCms\Cms\Dashboard\Widgets\CraftSupport;

beforeEach(function () {
    $this->component = resolve(CraftSupport::class);
});

it('can determine if it is new', function (int|string|null $id, bool $expected) {
    $this->component->id = $id;

    expect($this->component->getIsNew())->toBe($expected);
})->with([
    [null, true],
    [1, false],
    ['new-1', true],
]);

it('can register before save callback', function () {
    $triggered = false;

    $this->component::onBeforeSave(function (ComponentEvent $event) use (&$triggered) {
        $triggered = true;
        expect($event->component)->toBe($this->component);
        expect($event->isNew)->toBeTrue();

        $event->isValid = false;
    });

    expect($triggered)->toBeFalse();

    expect($this->component->beforeSave(true))->toBeFalse();

    expect($triggered)->toBeTrue();
});

it('can register after save callback', function () {
    $triggered = false;

    $this->component::onAfterSave(function (ComponentEvent $event) use (&$triggered) {
        $triggered = true;
        expect($event->component)->toBe($this->component);
        expect($event->isNew)->toBeTrue();
    });

    expect($triggered)->toBeFalse();

    $this->component->afterSave(true);

    expect($triggered)->toBeTrue();
});

it('can register before delete callback', function () {
    $triggered = false;

    $this->component::onBeforeDelete(function (ComponentEvent $event) use (&$triggered) {
        $triggered = true;
        expect($event->component)->toBe($this->component);
        expect($event->isNew)->toBeFalse();

        $event->isValid = false;
    });

    expect($triggered)->toBeFalse();

    expect($this->component->beforeDelete())->toBeFalse();

    expect($triggered)->toBeTrue();
});

it('can register before apply delete callback', function () {
    $triggered = false;

    $this->component::onBeforeApplyDelete(function (ComponentEvent $event) use (&$triggered) {
        $triggered = true;
        expect($event->component)->toBe($this->component);
        expect($event->isNew)->toBeFalse();
    });

    expect($triggered)->toBeFalse();

    $this->component->beforeApplyDelete();

    expect($triggered)->toBeTrue();
});

it('can register after delete callback', function () {
    $triggered = false;

    $this->component::onAfterDelete(function (ComponentEvent $event) use (&$triggered) {
        $triggered = true;
        expect($event->component)->toBe($this->component);
        expect($event->isNew)->toBeFalse();
    });

    expect($triggered)->toBeFalse();

    $this->component->afterDelete();

    expect($triggered)->toBeTrue();
});
