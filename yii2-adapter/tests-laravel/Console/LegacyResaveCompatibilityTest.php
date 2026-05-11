<?php

declare(strict_types=1);

use craft\console\Controller;
use craft\console\controllers\ResaveController;
use craft\events\DefineConsoleActionsEvent;
use CraftCms\Cms\Element\Events\ElementResaveCommandsResolving;
use CraftCms\Yii2Adapter\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use yii\base\Event as YiiEvent;

uses(TestCase::class);

beforeEach(function() {
    Event::forget(ElementResaveCommandsResolving::class);
    ResaveController::registerEvents();
});

afterEach(function() {
    Event::forget(ElementResaveCommandsResolving::class);
    YiiEvent::off(ResaveController::class, Controller::EVENT_DEFINE_ACTIONS);
});

it('bridges legacy define actions handlers into define resave commands', function() {
    YiiEvent::on(ResaveController::class, Controller::EVENT_DEFINE_ACTIONS, function(DefineConsoleActionsEvent $event) {
        $event->actions['products'] = [
            'helpSummary' => 'Re-saves products.',
            'action' => static fn() => 0,
        ];
    });

    $event = new ElementResaveCommandsResolving();
    event($event);

    expect($event->commands)
        ->toHaveKey('craft:resave:products')
        ->and($event->commands['craft:resave:products']['description'])->toBe('Re-saves products.');
});

it('bridges legacy resave actions into command metadata', function() {
    YiiEvent::on(ResaveController::class, Controller::EVENT_DEFINE_ACTIONS, function(DefineConsoleActionsEvent $event) {
        $event->actions['products'] = [
            'helpSummary' => 'Re-saves products.',
            'action' => static fn() => 0,
        ];
    });

    $event = new ElementResaveCommandsResolving();
    event($event);

    expect($event->commands)->toHaveKey('craft:resave:products');
});

it('bridges built-in legacy category and tag actions into command metadata', function() {
    $this->artisan('craft:add-categories-support --force --no-interaction')->assertSuccessful();
    $this->artisan('craft:add-tags-support --force --no-interaction')->assertSuccessful();
    CraftCms\Yii2Adapter\DeprecatedConcepts::resetSupport();

    $event = new ElementResaveCommandsResolving();
    event($event);

    expect($event->commands)
        ->toHaveKey('craft:resave:categories')
        ->toHaveKey('craft:resave:tags');
});

it('keeps the legacy controller resave api available', function() {
    $controller = new ResaveController('resave', app('Craft'));

    expect(method_exists($controller, 'resaveElements'))->toBeTrue()
        ->and(method_exists($controller, 'saveElements'))->toBeTrue()
        ->and(method_exists($controller, 'actionCategories'))->toBeTrue()
        ->and(method_exists($controller, 'actionTags'))->toBeTrue();
});
