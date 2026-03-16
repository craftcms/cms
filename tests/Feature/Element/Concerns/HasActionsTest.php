<?php

declare(strict_types=1);

use craft\elements\actions\Delete;
use craft\elements\actions\Duplicate;
use craft\elements\actions\Edit;
use craft\elements\actions\SetStatus;
use craft\elements\actions\View;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Events\RegisterActions;
use CraftCms\Cms\Entry\Elements\Entry;
use Illuminate\Support\Facades\Event;

function extractActionTypes(array $actions): array
{
    return array_map(fn ($action) => is_array($action) ? $action['type'] : $action, $actions);
}

test('includes default actions', function () {
    $actions = Entry::actions('all');
    $actionTypes = extractActionTypes($actions);

    expect($actionTypes)->toContain(Duplicate::class)
        ->and($actionTypes)->toContain(Edit::class)
        ->and($actionTypes)->toContain(View::class)
        ->and($actionTypes)->toContain(Delete::class);
});

test('includes SetStatus for elements with status support', function () {
    // Entry has status support via includeSetStatusAction returning true
    $actions = Entry::actions('all');
    $actionTypes = extractActionTypes($actions);

    expect($actionTypes)->toContain(SetStatus::class);
});

test('defineActions merges with default actions', function () {
    $class = new class extends Element
    {
        protected static function defineActions(string $source): array
        {
            return [Foo::class];
        }
    };

    expect($class::actions('all'))->toContain(Foo::class);
});

test('RegisterActions event allows adding custom actions', function () {
    Event::listen(function (RegisterActions $event) {
        if ($event->elementType === Entry::class) {
            $event->actions[] = CustomAction::class;
        }
    });

    $actions = Entry::actions('all');
    $actionTypes = extractActionTypes($actions);

    expect($actionTypes)->toContain(CustomAction::class);
});

class CustomAction {}
class Foo {}
