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
        ->assertJsonPath('form.scope', ['new-widget-settings'])
        ->assertJsonPath('form.values.new-widget-settings.limit', 25);

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
        'settings' => [], 'colspan' => 2, 'ids' => json_encode([$widget->id]),
    ])->assertUnprocessable();

    expect(WidgetModel::query()->findOrFail($widget->id)->getAttributes())->toBe($before);
})->with(['update', 'delete', 'updateColspan', 'reorder']);

it('rejects adding another instance of a singleton widget', function () {
    UserModel::query()->whereKey(Auth::id())->update(['hasDashboard' => true]);
    app(Dashboard::class)->saveWidget(app(Dashboard::class)->createWidget(Updates::class));

    postJson(action([WidgetsController::class, 'store']), ['type' => Updates::class])
        ->assertJsonValidationErrorFor('type');

    expect(WidgetModel::query()->count())->toBe(1);
});
