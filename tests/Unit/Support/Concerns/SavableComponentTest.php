<?php

declare(strict_types=1);

use CraftCms\Cms\Component\Events\ComponentDeleteApplying;
use CraftCms\Cms\Component\Events\ComponentDeleted;
use CraftCms\Cms\Component\Events\ComponentDeleting;
use CraftCms\Cms\Component\Events\ComponentSaved;
use CraftCms\Cms\Component\Events\SavingComponent;
use CraftCms\Cms\Dashboard\Widgets\CraftSupport;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->component = app(CraftSupport::class);
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

    Event::listen(function (SavingComponent $event) use (&$triggered) {
        $triggered = true;
        expect($event->component)->toBe($this->component);
        expect($event->isNew)->toBeTrue();

        $event->isValid = false;
    });

    expect($triggered)->toBeFalse();

    expect($this->component->beforeSave(true))->toBeFalse();

    expect($triggered)->toBeTrue();
});

it('filters component helper listeners and supports Laravel listener classes', function () {
    SavableComponentTestListener::$events = [];

    CraftSupport::onBeforeSave(SavableComponentTestListener::class);

    event(new SavingComponent(new stdClass, true));

    expect(SavableComponentTestListener::$events)->toBeEmpty();

    $this->component->beforeSave(true);

    expect(SavableComponentTestListener::$events)
        ->toHaveCount(1)
        ->and(SavableComponentTestListener::$events[0]->component)->toBe($this->component);
});

it('can register after save callback', function () {
    $triggered = false;

    Event::listen(function (ComponentSaved $event) use (&$triggered) {
        $triggered = true;
        expect($event->component)->toBe($this->component);
        expect($event->isNew)->toBeTrue();
    });

    expect($triggered)->toBeFalse();

    $this->component->afterSave(true);

    expect($triggered)->toBeTrue();
});

class SavableComponentTestListener
{
    /** @var array<int, SavingComponent> */
    public static array $events = [];

    public function handle(SavingComponent $event): void
    {
        self::$events[] = $event;
    }
}

it('can register before delete callback', function () {
    $triggered = false;

    Event::listen(function (ComponentDeleting $event) use (&$triggered) {
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

    Event::listen(function (ComponentDeleteApplying $event) use (&$triggered) {
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

    Event::listen(function (ComponentDeleted $event) use (&$triggered) {
        $triggered = true;
        expect($event->component)->toBe($this->component);
        expect($event->isNew)->toBeFalse();
    });

    expect($triggered)->toBeFalse();

    $this->component->afterDelete();

    expect($triggered)->toBeTrue();
});
