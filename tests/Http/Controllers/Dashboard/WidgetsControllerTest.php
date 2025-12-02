<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Models\Widget as WidgetModel;
use CraftCms\Cms\Dashboard\Widgets\CraftSupport;
use CraftCms\Cms\Dashboard\Widgets\Feed;
use CraftCms\Cms\Dashboard\Widgets\Updates;
use CraftCms\Cms\Dashboard\Widgets\Widget;
use CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::first());

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

    expect($response->json('info'))->not()->toBeEmpty();
    expect($response->json('headHtml'))->not()->toBeEmpty();
    expect($response->json('bodyHtml'))->not()->toBeEmpty();

    expect(WidgetModel::count())->toBe(1);
    tap(WidgetModel::query()->firstOrFail(), function (WidgetModel $widget) {
        expect(Widget::fromConfig($widget)->url)->toBe('https://craftcms.com/news.rss');
    });
});

test('store needs a valid type', function () {
    postJson(action([WidgetsController::class, 'store']), [
        'type' => 'invalid',
    ])->assertUnprocessable()
        ->assertJsonValidationErrorFor('type');
});

it('can store namespaced settings', function () {
    postJson(action([WidgetsController::class, 'store']), [
        'type' => Feed::class,
        'settingsNamespace' => 'test',
        'test' => [
            'title' => 'Craft News',
            'url' => 'https://craftcms.com/news.rss',
        ],
    ])->assertOk();

    expect(WidgetModel::count())->toBe(1);
});

it('can update a widget with settings', function () {
    $dashboard = resolve(Dashboard::class);
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
        "widget{$widget->id}-settings" => [
            'title' => 'Craft News',
            'limit' => 10,
            'url' => 'https://craftcms.com/feed.rss',
        ],
    ])->assertOk();

    expect($response->json('info'))->not()->toBeEmpty();
    expect($response->json('headHtml'))->not()->toBeEmpty();
    expect($response->json('bodyHtml'))->not()->toBeEmpty();

    expect(Widget::fromConfig(WidgetModel::first())->url)->toBe('https://craftcms.com/feed.rss');
});

it('validates when updating', function () {
    $dashboard = resolve(Dashboard::class);
    $dashboard->saveWidget($widget = $dashboard->createWidget([
        'type' => Feed::class,
        'settings' => [
            'title' => 'Craft News',
            'url' => 'https://craftcms.com/news.rss',
        ],
    ]));

    postJson(action([WidgetsController::class, 'update']), [
        'widgetId' => $widget->id,
        "widget{$widget->id}-settings" => [],
    ])
        ->assertJsonValidationErrorFor('title')
        ->assertJsonValidationErrorFor('url')
        ->assertJsonValidationErrorFor('limit');
});

it('can update the colspan of a widget', function () {
    $dashboard = resolve(Dashboard::class);
    $dashboard->saveWidget($widget = $dashboard->createWidget(Updates::class));

    expect(WidgetModel::first()->colspan)->toBeNull();

    postJson(action([WidgetsController::class, 'updateColspan']), [
        'id' => $widget->id,
        'colspan' => 2,
    ])->assertOk();

    expect(WidgetModel::first()->colspan)->toBe(2);
});

it('colspan must be between 1 and 3', function () {
    $dashboard = resolve(Dashboard::class);
    $dashboard->saveWidget($widget = $dashboard->createWidget(Updates::class));

    postJson(action([WidgetsController::class, 'updateColspan']), [
        'id' => $widget->id,
        'colspan' => 0,
    ])->assertJsonValidationErrorFor('colspan');

    postJson(action([WidgetsController::class, 'updateColspan']), [
        'id' => $widget->id,
        'colspan' => 4,
    ])->assertJsonValidationErrorFor('colspan');
});

it('can reorder widgets', function () {
    $dashboard = resolve(Dashboard::class);
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
    $dashboard = resolve(Dashboard::class);
    $dashboard->saveWidget($widget1 = $dashboard->createWidget(Updates::class));

    expect(WidgetModel::count())->toBe(1);

    postJson(action([WidgetsController::class, 'delete']), [
        'id' => $widget1->id,
    ])->assertOk();

    expect(WidgetModel::count())->toBe(0);
});
