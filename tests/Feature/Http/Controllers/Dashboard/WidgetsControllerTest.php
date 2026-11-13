<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Events\WidgetDeleting;
use CraftCms\Cms\Dashboard\Events\WidgetSaving;
use CraftCms\Cms\Dashboard\Models\Widget as WidgetModel;
use CraftCms\Cms\Dashboard\Widgets\CraftSupport;
use CraftCms\Cms\Dashboard\Widgets\Feed;
use CraftCms\Cms\Dashboard\Widgets\Updates;
use CraftCms\Cms\Dashboard\Widgets\Widget;
use CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->one());

    WidgetModel::all()->each->delete();
});

it('requires authentication', function (string $action) {
    Auth::logout();

    postJson(action([WidgetsController::class, $action]))
        ->assertUnauthorized();
})->with([
    'store',
    'update',
    'updateColspan',
    'updateColor',
    'reorder',
    'delete',
]);

it('can store a widget with settings', function () {
    $response = postJson(action([WidgetsController::class, 'store']), [
        'type' => Feed::class,
        'settings' => [
            'title' => 'Craft News',
            'url' => 'https://craftcms.com/news.rss',
        ],
    ])->assertOk();

    $response->assertJsonPath('info.title', 'Craft News');

    expect(WidgetModel::count())->toBe(1);
    tap(WidgetModel::query()->firstOrFail(), function (WidgetModel $widget) {
        expect(Widget::fromConfig($widget)->url)->toBe('https://craftcms.com/news.rss');
    });
});

it('can refresh widget settings without saving', function () {
    postJson(action([WidgetsController::class, 'refreshSettings']), [
        'type' => Feed::class,
        'settings' => [
            'title' => 'Craft News',
            'url' => 'https://craftcms.com/news.rss',
            'limit' => 25,
        ],
        'namespace' => 'new-widget-settings',
    ])->assertOk()
        ->assertJsonPath('ui.scope', ['new-widget-settings'])
        ->assertJsonPath('ui.values.new-widget-settings.limit', 25);

    expect(WidgetModel::count())->toBe(0);
});

test('store needs a valid type', function () {
    postJson(action([WidgetsController::class, 'store']), [
        'type' => 'invalid',
    ])->assertUnprocessable()
        ->assertJsonValidationErrorFor('type');
});

it('can update a widget with settings', function () {
    $dashboard = app(Dashboard::class);
    $dashboard->saveWidget($widget = $dashboard->createWidget([
        'type' => Feed::class,
        'settings' => [
            'title' => 'Craft News',
            'url' => 'https://craftcms.com/news.rss',
        ],
    ]));

    expect(Widget::fromConfig(WidgetModel::first())->url)->toBe('https://craftcms.com/news.rss');

    $response = postJson(action([WidgetsController::class, 'update']), [
        'widgetId' => $widget->id,
        'settings' => [
            'title' => 'Craft News',
            'limit' => 10,
            'url' => 'https://craftcms.com/feed.rss',
        ],
    ])->assertOk();

    $response->assertJsonPath('info.title', 'Craft News')->assertJsonPath('info.data.limit', 10);

    expect(WidgetModel::query()->findOrFail($widget->id)->settings)
        ->toMatchArray(['title' => 'Craft News', 'url' => 'https://craftcms.com/feed.rss', 'limit' => 10]);
});

it('validates when updating', function () {
    $dashboard = app(Dashboard::class);
    $dashboard->saveWidget($widget = $dashboard->createWidget([
        'type' => Feed::class,
        'settings' => [
            'title' => 'Craft News',
            'url' => 'https://craftcms.com/news.rss',
        ],
    ]));

    postJson(action([WidgetsController::class, 'update']), [
        'widgetId' => $widget->id,
        'settings' => [],
    ])
        ->assertJsonValidationErrorFor('title')
        ->assertJsonValidationErrorFor('url')
        ->assertJsonValidationErrorFor('limit');
});

it('can update the colspan of a widget', function () {
    $dashboard = app(Dashboard::class);
    $dashboard->saveWidget($widget = $dashboard->createWidget(Updates::class));

    expect(WidgetModel::first()->colspan)->toBeNull();

    postJson(action([WidgetsController::class, 'updateColspan']), [
        'id' => $widget->id,
        'colspan' => 2,
    ])->assertOk();

    expect(WidgetModel::first()->colspan)->toBe(2);
});

it('makes new widgets gray', function () {
    postJson(action([WidgetsController::class, 'store']), ['type' => Updates::class])
        ->assertOk()
        ->assertJsonPath('info.color', 'gray');

    expect(WidgetModel::first()->color)->toBe('gray');
});

it('can change and clear the color of a widget', function () {
    $dashboard = app(Dashboard::class);
    $dashboard->saveWidget($widget = $dashboard->createWidget(Updates::class));

    postJson(action([WidgetsController::class, 'updateColor']), [
        'id' => $widget->id,
        'color' => 'teal',
    ])->assertOk();

    expect(WidgetModel::first()->color)->toBe('teal');

    postJson(action([WidgetsController::class, 'updateColor']), [
        'id' => $widget->id,
        'color' => null,
    ])->assertOk();

    expect(WidgetModel::first()->color)->toBeNull()
        ->and($dashboard->getWidgetById($widget->id)->color)->toBeNull();
});

it('only accepts palette colors for a widget', function (array $payload) {
    $dashboard = app(Dashboard::class);
    $dashboard->saveWidget($widget = $dashboard->createWidget(Updates::class));

    postJson(action([WidgetsController::class, 'updateColor']), ['id' => $widget->id, ...$payload])
        ->assertJsonValidationErrorFor('color');

    expect(WidgetModel::first()->color)->toBe('gray');
})->with([
    'unknown color' => [['color' => 'chartreuse']],
    'hex color' => [['color' => '#ff0000']],
    'missing color' => [[]],
]);

it('keeps a widget’s color when its settings are saved', function () {
    $dashboard = app(Dashboard::class);
    $dashboard->saveWidget($widget = $dashboard->createWidget([
        'type' => Feed::class,
        'color' => 'violet',
        'settings' => ['title' => 'Craft News', 'url' => 'https://craftcms.com/news.rss'],
    ]));

    postJson(action([WidgetsController::class, 'update']), [
        'widgetId' => $widget->id,
        'settings' => ['title' => 'Craft News', 'limit' => 10, 'url' => 'https://craftcms.com/feed.rss'],
    ])->assertOk()->assertJsonPath('info.color', 'violet');

    expect(WidgetModel::query()->findOrFail($widget->id)->color)->toBe('violet');
});

it('restores a deleted widget’s color, including none', function (?string $color) {
    postJson(action([WidgetsController::class, 'store']), ['type' => Updates::class, 'color' => $color])
        ->assertOk()
        ->assertJsonPath('info.color', $color);

    expect(WidgetModel::first()->color)->toBe($color);
})->with(['amber', null]);

it('colspan must be between 1 and 4', function () {
    $dashboard = app(Dashboard::class);
    $dashboard->saveWidget($widget = $dashboard->createWidget(Updates::class));

    postJson(action([WidgetsController::class, 'updateColspan']), [
        'id' => $widget->id,
        'colspan' => 0,
    ])->assertJsonValidationErrorFor('colspan');

    postJson(action([WidgetsController::class, 'updateColspan']), [
        'id' => $widget->id,
        'colspan' => 5,
    ])->assertJsonValidationErrorFor('colspan');
});

it('can reorder widgets', function () {
    $dashboard = app(Dashboard::class);
    $dashboard->saveWidget($widget1 = $dashboard->createWidget(Updates::class));
    $dashboard->saveWidget($widget2 = $dashboard->createWidget(CraftSupport::class));

    expect(WidgetModel::query()->orderBy('sortOrder')->pluck('id')->all())->toBe([$widget1->id, $widget2->id]);

    postJson(action([WidgetsController::class, 'reorder']), [
        // For some reason the frontend encodes this as json so we test this behavior here
        'ids' => json_encode([$widget2->id, $widget1->id]),
    ])->assertOk();

    expect(WidgetModel::query()->orderBy('sortOrder')->pluck('id')->all())->toBe([$widget2->id, $widget1->id]);
});

it('can delete a widget', function () {
    $dashboard = app(Dashboard::class);
    $dashboard->saveWidget($widget1 = $dashboard->createWidget(Updates::class));

    expect(WidgetModel::count())->toBe(1);

    postJson(action([WidgetsController::class, 'delete']), [
        'id' => $widget1->id,
    ])->assertOk();

    expect(WidgetModel::count())->toBe(0);
});

it('does not report a cancelled widget save or deletion as successful', function (string $operation) {
    $dashboard = app(Dashboard::class);
    $dashboard->saveWidget($widget = $dashboard->createWidget(Updates::class));

    $eventClass = $operation === 'update'
        ? WidgetSaving::class
        : WidgetDeleting::class;

    Event::listen($eventClass, function ($event) {
        $event->isValid = false;
    });

    postJson(action([WidgetsController::class, $operation]), [
        'id' => $widget->id, 'widgetId' => $widget->id, 'settings' => [],
    ])->assertUnprocessable();

    expect(WidgetModel::query()->whereKey($widget->id)->exists())->toBeTrue();
})->with(['update', 'delete']);

it('rejects changes to another user’s widget', function (string $operation) {
    $dashboard = app(Dashboard::class);
    $dashboard->saveWidget($widget = $dashboard->createWidget(Updates::class));

    $before = WidgetModel::query()->findOrFail($widget->id)->getAttributes();
    actingAs(UserModel::factory()->admin()->create());

    postJson(action([WidgetsController::class, $operation]), [
        'id' => $widget->id, 'widgetId' => $widget->id,
        'settings' => [], 'colspan' => 2, 'color' => 'red', 'ids' => json_encode([$widget->id]),
    ])->assertUnprocessable();

    expect(WidgetModel::query()->findOrFail($widget->id)->getAttributes())->toBe($before);
})->with(['update', 'delete', 'updateColspan', 'updateColor', 'reorder']);

it('rejects adding another instance of a singleton widget', function () {
    UserModel::query()->whereKey(Auth::id())->update(['hasDashboard' => true]);
    app(Dashboard::class)->saveWidget(app(Dashboard::class)->createWidget(Updates::class));

    postJson(action([WidgetsController::class, 'store']), ['type' => Updates::class])
        ->assertJsonValidationErrorFor('type');

    expect(WidgetModel::query()->count())->toBe(1);
});
