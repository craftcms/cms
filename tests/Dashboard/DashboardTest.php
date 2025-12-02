<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Events\RegisterWidgetTypes;
use CraftCms\Cms\Dashboard\Events\WidgetDeleting;
use CraftCms\Cms\Dashboard\Events\WidgetSaved;
use CraftCms\Cms\Dashboard\Events\WidgetSaving;
use CraftCms\Cms\Dashboard\Models\Widget as WidgetModel;
use CraftCms\Cms\Dashboard\Widgets\CraftSupport;
use CraftCms\Cms\Dashboard\Widgets\Feed;
use CraftCms\Cms\Dashboard\Widgets\Widget;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->dashboard = resolve(Dashboard::class);

    actingAs(User::first());

    WidgetModel::all()->each->delete();
});

it('can get all widget types', function () {
    expect($this->dashboard->getAllWidgetTypes())->not()->toBeEmpty();
});

it('can register additional widgets', function () {
    class MyWidget extends Widget {}

    Event::listen(RegisterWidgetTypes::class, function (RegisterWidgetTypes $event) {
        $event->types[] = MyWidget::class;
    });

    expect($this->dashboard->getAllWidgetTypes())->toContain(MyWidget::class);
});

it('can create a widget from config', function () {
    $config = [
        'type' => Feed::class,
        'settings' => [
            'url' => 'https://craftcms.com/news.rss',
            'title' => 'Craft News',
        ],
    ];

    /** @var Feed $widget */
    $widget = $this->dashboard->createWidget($config);
    expect($widget)->toBeInstanceOf(Feed::class);
    tap($widget, function (Feed $widget) {
        expect($widget->url)->toBe('https://craftcms.com/news.rss');
        expect($widget->title)->toBe('Craft News');
    });
});

it('can create a widget from type', function () {
    $widget = $this->dashboard->createWidget([
        'type' => Feed::class,
        'settings' => [
            'title' => 'Craft News',
            'url' => 'https://craftcms.com/news.rss',
        ],
    ]);
    expect($widget)->toBeInstanceOf(Feed::class);
});

it('can get all widgets and adds default widgets', function () {
    expect(WidgetModel::count())->toBe(0);

    expect($this->dashboard->getAllWidgets())->not()->toBeEmpty();

    expect(WidgetModel::count())->toBeGreaterThan(0);
});

it('can test if a user has a widget', function () {
    expect($this->dashboard->doesUserHaveWidget(Feed::class))->toBeFalse();

    $this->dashboard->saveWidget($this->dashboard->createWidget([
        'type' => Feed::class,
        'settings' => [
            'title' => 'Craft News',
            'url' => 'https://craftcms.com/news.rss',
        ],
    ]));

    expect($this->dashboard->doesUserHaveWidget(Feed::class))->toBeTrue();
});

it('can save a new widget for a user', function () {
    Event::fake();
    Event::listen(WidgetSaving::class, function () {});
    Event::listen(WidgetSaved::class, function () {});

    expect(WidgetModel::count())->toBe(0);

    $widget = $this->dashboard->createWidget([
        'type' => Feed::class,
        'settings' => [
            'title' => 'Craft News',
            'url' => 'https://craftcms.com/news.rss',
        ],
    ]);

    expect(WidgetModel::count())->toBe(0);

    $this->dashboard->saveWidget($widget);

    expect(WidgetModel::count())->toBe(1);

    Event::assertDispatchedTimes(WidgetSaving::class, 1);
    Event::assertDispatchedTimes(WidgetSaved::class, 1);
});

it('can cancel saving a widget with the event', function () {
    Event::listen(WidgetSaving::class, function (WidgetSaving $event) {
        $event->isValid = false;
    });

    expect(WidgetModel::count())->toBe(0);

    $widget = $this->dashboard->createWidget([
        'type' => Feed::class,
        'settings' => [
            'title' => 'Craft News',
            'url' => 'https://craftcms.com/news.rss',
        ],
    ]);

    expect(WidgetModel::count())->toBe(0);

    $this->dashboard->saveWidget($widget);

    expect(WidgetModel::count())->toBe(0);
});

it('can delete a widget by id', function () {
    $this->dashboard->saveWidget($widget = $this->dashboard->createWidget([
        'type' => Feed::class,
        'settings' => [
            'title' => 'Craft News',
            'url' => 'https://craftcms.com/news.rss',
        ],
    ]));

    expect(WidgetModel::count())->toBe(1);

    $this->dashboard->deleteWidgetById($widget->id);

    expect(WidgetModel::count())->toBe(0);
});

it('can cancel deleting a widget by id', function () {
    Event::listen(WidgetDeleting::class, function (WidgetDeleting $event) {
        $event->isValid = false;
    });

    $this->dashboard->saveWidget($widget = $this->dashboard->createWidget([
        'type' => Feed::class,
        'settings' => [
            'title' => 'Craft News',
            'url' => 'https://craftcms.com/news.rss',
        ],
    ]));

    expect(WidgetModel::count())->toBe(1);

    $this->dashboard->deleteWidgetById($widget->id);

    expect(WidgetModel::count())->toBe(1);
});

it('can delete a widget by instance', function () {
    $this->dashboard->saveWidget($widget = $this->dashboard->createWidget([
        'type' => Feed::class,
        'settings' => [
            'title' => 'Craft News',
            'url' => 'https://craftcms.com/news.rss',
        ],
    ]));

    expect(WidgetModel::count())->toBe(1);

    $this->dashboard->deleteWidget($widget);

    expect(WidgetModel::count())->toBe(0);
});

it('can cancel deleting a widget by instance', function () {
    Event::listen(WidgetDeleting::class, function (WidgetDeleting $event) {
        $event->isValid = false;
    });

    $this->dashboard->saveWidget($widget = $this->dashboard->createWidget([
        'type' => Feed::class,
        'settings' => [
            'title' => 'Craft News',
            'url' => 'https://craftcms.com/news.rss',
        ],
    ]));

    expect(WidgetModel::count())->toBe(1);

    $this->dashboard->deleteWidget($widget);

    expect(WidgetModel::count())->toBe(1);
});

it('can reorder widgets', function () {
    $this->dashboard->saveWidget($widget1 = $this->dashboard->createWidget([
        'type' => Feed::class,
        'settings' => [
            'title' => 'Craft News',
            'url' => 'https://craftcms.com/news.rss',
        ],
    ]));
    $this->dashboard->saveWidget($widget2 = $this->dashboard->createWidget(CraftSupport::class));

    expect(WidgetModel::query()->orderBy('sortOrder')->pluck('id')->all())->toBe([$widget1->id, $widget2->id]);

    $this->dashboard->reorderWidgets([$widget2->id, $widget1->id]);

    expect(WidgetModel::query()->orderBy('sortOrder')->pluck('id')->all())->toBe([$widget2->id, $widget1->id]);
});

it('ignores non existing ids', function () {
    $this->dashboard->saveWidget($widget1 = $this->dashboard->createWidget([
        'type' => Feed::class,
        'settings' => [
            'title' => 'Craft News',
            'url' => 'https://craftcms.com/news.rss',
        ],
    ]));
    $this->dashboard->saveWidget($widget2 = $this->dashboard->createWidget(CraftSupport::class));

    expect(WidgetModel::query()->orderBy('sortOrder')->pluck('id')->all())->toBe([$widget1->id, $widget2->id]);

    $this->dashboard->reorderWidgets([$widget2->id, $widget1->id, 10]);

    expect(WidgetModel::query()->orderBy('sortOrder')->pluck('id')->all())->toBe([$widget2->id, $widget1->id]);
});

it('can change the colspan of a widget', function () {
    $this->dashboard->saveWidget($widget = $this->dashboard->createWidget([
        'type' => Feed::class,
        'settings' => [
            'title' => 'Craft News',
            'url' => 'https://craftcms.com/news.rss',
        ],
    ]));

    expect(WidgetModel::first()->colspan)->toBeNull();

    $this->dashboard->changeWidgetColspan($widget->id, 2);

    expect(WidgetModel::first()->colspan)->toBe(2);
});
