<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\CustomWidgets;
use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Models\Widget as WidgetModel;
use CraftCms\Cms\Dashboard\Widgets\Custom;
use CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\File;

use function CraftCms\Cms\currentUser;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->widgetsPath = resource_path('widgets');
    File::ensureDirectoryExists($this->widgetsPath);
    File::cleanDirectory($this->widgetsPath);
});

afterEach(function () {
    File::deleteDirectory($this->widgetsPath);
});

it('discovers top-level Markdown files', function () {
    File::put("$this->widgetsPath/welcome.md", '# Welcome');
    File::put("$this->widgetsPath/.hidden.MD", '# Hidden');
    File::put("$this->widgetsPath/ignored.html", '<p>Ignored</p>');
    File::ensureDirectoryExists("$this->widgetsPath/nested");
    File::put("$this->widgetsPath/nested/ignored.md", '# Ignored');

    $definitions = app(CustomWidgets::class)->all();

    expect($definitions)
        ->toHaveCount(2)
        ->toHaveKeys(['path:.hidden.MD', 'path:welcome.md']);
});

it('ignores unsupported frontmatter', function (string $frontmatter) {
    File::put("$this->widgetsPath/widget.md", "$frontmatter\n# Widget");

    $definition = app(CustomWidgets::class)->all()->sole();

    expect($definition->id)->toBe('path:widget.md')
        ->and($definition->label)->toBeNull();
})->with([
    'unknown property' => "---\nunknown: true\n---",
    'list' => "---\n- ignored\n---",
]);

it('renders metadata and Markdown through Twig', function () {
    actingAs($user = User::find()->one());
    $user->setFriendlyName('<script>alert(1)</script>');

    File::put("$this->widgetsPath/welcome.md", <<<'MD'
---
handle: welcome
label: "R{{ '&' }}D {{ currentUser.friendlyName }}"
icon: hand-wave
maxColspan: 2
subtitle: 'Craft edition {{ CraftEdition }}'
---

## Welcome {{ currentUser.friendlyName }}

MD);

    $widget = app(Dashboard::class)->createWidget([
        'type' => Custom::class,
        'settings' => [
            'definitionId' => 'handle:welcome',
        ],
    ]);

    expect($widget)
        ->getDisplayName()->toBe('R&D '.currentUser()->friendlyName)
        ->getTitle()->toBe('R&D '.currentUser()->friendlyName)
        ->getSubtitle()->toStartWith('Craft edition ')
        ->getIcon()->toBe('hand-wave')
        ->getMaxColspan()->toBe(2)
        ->and($widget->getBodyHtml())
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
        ->not->toContain('<script>');
});

it('uses filename labels and respects a null title', function () {
    actingAs(User::find()->one());

    File::put("$this->widgetsPath/team-news.md", <<<'MD'
---
label: '{% if false %}Hidden{% endif %}'
title: null
---

MD);

    $widget = app(Dashboard::class)->createWidget([
        'type' => Custom::class,
        'settings' => [
            'definitionId' => 'path:team-news.md',
        ],
    ]);

    expect($widget)
        ->getDisplayName()->toBe('Team News')
        ->getTitle()->toBeNull();
});

it('coerces scalar frontmatter values', function () {
    File::put("$this->widgetsPath/widget.md", <<<'MD'
---
label: 123
icon: 456
maxColspan: '2'
title: true
subtitle: 1.5
showByDefault: 'yes'
---

# Widget
MD);

    $definition = app(CustomWidgets::class)->all()->sole();

    expect($definition)
        ->label->toBe('123')
        ->icon->toBe('456')
        ->maxColspan->toBe(2)
        ->title->toBe('1')
        ->subtitle->toBe('1.5')
        ->showByDefault->toBeTrue();
});

it('rejects invalid widget definitions', function (string $source, string $message) {
    File::put("$this->widgetsPath/invalid.md", $source);

    expect(fn () => app(CustomWidgets::class)->all())
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'invalid handle' => ["---\nhandle: invalid-handle\n---\n", 'invalid handle'],
    'numeric handle' => ["---\nhandle: 123\n---\n", 'invalid handle'],
    'reserved handle' => ["---\nhandle: fields\n---\n", 'invalid handle'],
    'array label' => ["---\nlabel: [Widget]\n---\n", 'frontmatter property [label] must be a string or null'],
    'invalid colspan' => ["---\nmaxColspan: 5\n---\n", 'must be an integer between 1 and 4'],
    'fractional colspan' => ["---\nmaxColspan: '2.5'\n---\n", 'must be an integer between 1 and 4'],
    'invalid default visibility' => ["---\nshowByDefault: sometimes\n---\n", 'frontmatter property [showByDefault] must be a boolean'],
]);

it('rejects duplicate handles case-insensitively', function () {
    File::put("$this->widgetsPath/first.md", "---\nhandle: News\n---\n");
    File::put("$this->widgetsPath/second.md", "---\nhandle: news\n---\n");

    expect(fn () => app(CustomWidgets::class)->all())
        ->toThrow(InvalidArgumentException::class, 'have the same handle');
});

it('stores only server-resolved custom widget identities', function () {
    actingAs(User::find()->one());
    File::put("$this->widgetsPath/welcome.md", "---\nhandle: welcome\n---\n# Welcome");

    $customWidgets = app(CustomWidgets::class);
    $type = $customWidgets->find('handle:welcome')->type();

    postJson(action([WidgetsController::class, 'store']), [
        'type' => $type,
    ])->assertOk();

    $record = WidgetModel::query()->sole();

    expect($record)
        ->type->toBe(Custom::class)
        ->settings->toBe(['definitionId' => 'handle:welcome']);

    postJson(action([WidgetsController::class, 'store']), [
        'type' => "$type-invalid",
    ])->assertJsonValidationErrorFor('type');
});

it('shows custom widgets in the add menu based on selection', function (bool $selected, bool $selectable) {
    actingAs(User::find()->one());
    File::put("$this->widgetsPath/welcome.md", "---\nhandle: welcome\n---\n# Welcome");

    $type = app(CustomWidgets::class)->find('handle:welcome')->type();

    if ($selected) {
        $dashboard = app(Dashboard::class);
        $dashboard->saveWidget($dashboard->createWidget([
            'type' => Custom::class,
            'settings' => [
                'definitionId' => 'handle:welcome',
            ],
        ]));
    }

    get(route('craft.cp.dashboard'))
        ->assertOk()
        ->assertViewHas('widgetTypes', fn ($widgetTypes) => $widgetTypes->get($type)['selectable'] === $selectable);
})->with([
    'unselected' => [false, true],
    'selected' => [true, false],
]);

it('adds default custom widgets to new dashboards', function () {
    actingAs(User::find()->one());
    File::put("$this->widgetsPath/default.md", "---\nshowByDefault: true\n---\n# Default");
    File::put("$this->widgetsPath/optional.md", "---\nshowByDefault: false\n---\n# Optional");

    $widgets = app(Dashboard::class)->getAllWidgets();

    expect($widgets->whereInstanceOf(Custom::class)->pluck('definitionId')->all())
        ->toBe(['path:default.md']);
});

it('does not backfill defaults onto initialized dashboards', function () {
    actingAs(User::find()->one());
    File::put("$this->widgetsPath/default.md", "---\nshowByDefault: true\n---\n# Default");

    UserModel::query()->whereKey(currentUser()->id)->update(['hasDashboard' => true]);
    currentUser()->hasDashboard = true;

    expect(app(Dashboard::class)->getAllWidgets()->whereInstanceOf(Custom::class))->toBeEmpty();
});

it('validates custom defaults before initializing a dashboard', function () {
    actingAs(User::find()->one());
    File::put("$this->widgetsPath/invalid.md", "---\nmaxColspan: 5\n---\n");

    expect(fn () => app(Dashboard::class)->getAllWidgets())
        ->toThrow(InvalidArgumentException::class, 'must be an integer between 1 and 4')
        ->and(WidgetModel::query()->count())->toBe(0);
});

it('hides saved widgets whose definition no longer exists', function () {
    actingAs(User::find()->one());
    File::put("$this->widgetsPath/welcome.md", "---\nhandle: welcome\n---\n# Welcome");

    $dashboard = app(Dashboard::class);
    $dashboard->saveWidget($widget = $dashboard->createWidget([
        'type' => Custom::class,
        'settings' => [
            'definitionId' => 'handle:welcome',
        ],
    ]));

    File::delete("$this->widgetsPath/welcome.md");

    expect($dashboard->getWidgetById($widget->id)->getBodyHtml())->toBeNull()
        ->and(WidgetModel::query()->whereKey($widget->id)->exists())->toBeTrue();
});

it('resolves handled widgets after their files are renamed', function () {
    actingAs(User::find()->one());
    File::put("$this->widgetsPath/old-name.md", "---\nhandle: welcome\n---\n# Welcome");

    $dashboard = app(Dashboard::class);
    $dashboard->saveWidget($widget = $dashboard->createWidget([
        'type' => Custom::class,
        'settings' => [
            'definitionId' => 'handle:welcome',
        ],
    ]));

    File::move("$this->widgetsPath/old-name.md", "$this->widgetsPath/new-name.md");

    expect($dashboard->getWidgetById($widget->id)->getBodyHtml())->toContain('Welcome');
});
