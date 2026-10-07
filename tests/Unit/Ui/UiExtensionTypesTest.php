<?php

declare(strict_types=1);

use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\Ui\Controls\Slug;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\Ui\Nodes\Notice;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Controls\ElementSelect;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\Nodes\Container;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Nodes\Tab;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiControlTypes;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiNodeTypes;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

it('registers core and plugin Node and Control types separately', function () {
    $nodeTypes = app(UiNodeTypes::class);
    $controlTypes = app(UiControlTypes::class);
    $coreNodeTypes = $nodeTypes->types()->all();
    $coreControlTypes = $controlTypes->types()->all();

    expect($coreNodeTypes)->toContain(Field::class, Tab::class)->not->toContain(Text::class)
        ->and($coreControlTypes)->toContain(Text::class, ElementSelect::class)->not->toContain(Field::class);

    new TestPlugin(app())->registerUiTypes($nodeTypes, $controlTypes);

    expect($nodeTypes->types()->all())->toBe([...$coreNodeTypes, Notice::class])
        ->and($controlTypes->types()->all())->toBe([...$coreControlTypes, Slug::class])
        ->and(fn () => $nodeTypes->register(Slug::class))->toThrow(InvalidArgumentException::class, Node::class)
        ->and(fn () => $controlTypes->register(Notice::class))->toThrow(InvalidArgumentException::class, Control::class);
});

it('builds Node containers from children, configuration closures, and conditions', function () {
    $field = Field::make('Title', Text::make('title'));
    $ui = Ui::make()
        ->addTab('Array tab', [$field])
        ->addTab('Closure tab', fn (Tab $tab) => $tab->add($field), 'custom-tab')
        ->addGroup('Array group', [$field])
        ->addGroup('Closure group', fn (Group $group) => $group->label('Configured group')->add($field), 'custom-group');

    [$arrayTab, $closureTab, $arrayGroup, $closureGroup] = $ui->nodes();
    $conditionalGroup = Group::make('conditional')
        ->when(true, fn (Group $group) => $group->add($field))
        ->when(false, fn (Group $group) => $group->add($field))
        ->unless(false, fn (Group $group) => $group->add($field))
        ->unless(true, fn (Group $group) => $group->add($field));

    expect($arrayTab)->toBeInstanceOf(Tab::class)
        ->and($arrayTab)->toBeInstanceOf(Container::class)
        ->and($arrayTab->uid())->toBe('array-tab')
        ->and($arrayTab->children())->toBe([$field])
        ->and($closureTab)->toBeInstanceOf(Tab::class)
        ->and($closureTab->uid())->toBe('custom-tab')
        ->and($closureTab->children())->toBe([$field])
        ->and($arrayGroup)->toBeInstanceOf(Group::class)
        ->and($arrayGroup)->toBeInstanceOf(Container::class)
        ->and($arrayGroup->uid())->toBe('array-group')
        ->and($arrayGroup->props())->toBe(['label' => 'Array group'])
        ->and($arrayGroup->children())->toBe([$field])
        ->and($closureGroup)->toBeInstanceOf(Group::class)
        ->and($closureGroup->uid())->toBe('custom-group')
        ->and($closureGroup->props())->toBe(['label' => 'Configured group'])
        ->and($closureGroup->children())->toBe([$field])
        ->and($conditionalGroup->children())->toBe([$field, $field]);
});

it('renders collapsible groups through the shared payload and PHP renderer', function () {
    $payload = app(UiResolver::class)->resolve(Ui::make([
        Group::make('links', [
            Field::make('URL', Text::make('url')->value('https://craftcms.com')),
        ])->label('Links')->collapsible(),
    ]), new UiContext(namespace: 'settings'));
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));

    expect($payload->nodes[0]->props)->toBe([
        'label' => 'Links',
        'collapsible' => true,
    ])->and($crawler->filter('craft-disclosure[data-ui-node="links"][label="Links"]'))->toHaveCount(1)
        ->and($crawler->filter('craft-disclosure craft-field-group[slot="content"] input[name="settings[url]"]'))->toHaveCount(1);
});

it('renders and submits test plugin types through the PHP renderer', function () {
    new TestPlugin(app())->registerUiTypes(app(UiNodeTypes::class), app(UiControlTypes::class));

    $payload = app(UiResolver::class)->resolve(Ui::make([
        new Notice('plugin-notice', 'Provided by the test plugin'),
        Field::make('Slug', Slug::make('slug')->value('custom-value')),
    ]), new UiContext(namespace: 'settings'));
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));

    expect($crawler->filter('[data-test-plugin-notice]')->text())->toBe('Provided by the test plugin')
        ->and($crawler->filter('[data-test-plugin-control][name="settings[slug]"][value="custom-value"][placeholder="plugin-slug"]'))->toHaveCount(1)
        ->and($payload->nodes[0]->props)->toBe(['message' => 'Provided by the test plugin'])
        ->and($payload->nodes[1]->control?->props)->toBe(['placeholder' => 'plugin-slug']);
});

it('rejects unregistered live types and non-JSON-safe properties', function () {
    $notice = new Notice('plugin-notice', 'Unregistered');

    expect(fn () => app(UiResolver::class)->resolve(Ui::make([$notice]), new UiContext))
        ->toThrow(InvalidArgumentException::class, Notice::class);

    expect(fn () => app(UiResolver::class)->resolve(Ui::make([
        Field::make()->control(Slug::make('slug')),
    ]), new UiContext))
        ->toThrow(InvalidArgumentException::class, Slug::class);

    app(UiNodeTypes::class)->register(Notice::class);
    $invalid = new class('invalid', 'message') extends Notice
    {
        public function props(): array
        {
            return ['callback' => fn () => null];
        }
    };
    app(UiNodeTypes::class)->register($invalid::class);

    expect(fn () => app(UiResolver::class)->resolve(Ui::make([$invalid]), new UiContext))
        ->toThrow(InvalidArgumentException::class, 'JSON-safe');

    $invalidFloat = new class('invalid-float', 'message') extends Notice
    {
        public function props(): array
        {
            return ['number' => NAN];
        }
    };
    app(UiNodeTypes::class)->register($invalidFloat::class);

    expect(fn () => app(UiResolver::class)->resolve(Ui::make([$invalidFloat]), new UiContext))
        ->toThrow(InvalidArgumentException::class, 'JSON-safe');
});

it('rejects registered renderer exceptions without returning a partial PHP form', function () {
    $broken = new class('broken', 'message') extends Notice
    {
        public static function renderHtml(NodePayload $node, UiPayload $payload, UiHtmlRenderer $renderer): string
        {
            throw new RuntimeException('Test plugin renderer failed.');
        }
    };
    app(UiNodeTypes::class)->register($broken::class);
    $payload = app(UiResolver::class)->resolve(Ui::make([$broken]), new UiContext);
    $type = $broken::class;

    expect(fn () => app(UiHtmlRenderer::class)->render($payload))
        ->toThrow(RuntimeException::class, "Failed to render UI Node [{$type}] with component [test-plugin:notice] at [broken]: Test plugin renderer failed.");
});

it('reports extension context for invalid definitions', function () {
    new TestPlugin(app())->registerUiTypes(app(UiNodeTypes::class), app(UiControlTypes::class));

    expect(fn () => app(UiResolver::class)->resolve(Ui::make([
        new Notice('', 'Missing identity'),
    ]), new UiContext))
        ->toThrow(InvalidArgumentException::class, 'with component [test-plugin:notice] at [unknown] requires a stable UID')
        ->and(fn () => app(UiResolver::class)->resolve(Ui::make([
            Field::make()->control(Slug::make([])),
        ]), new UiContext))
        ->toThrow(InvalidArgumentException::class, 'requires a path; component [test-plugin:slug], identity [unknown]')
        ->and(fn () => app(UiResolver::class)->resolve(Ui::make([
            Field::make()->control(Slug::make([''])),
        ]), new UiContext))
        ->toThrow(InvalidArgumentException::class, 'UI Control ['.Slug::class.'] with component [test-plugin:slug] at [unknown] paths must contain non-empty string segments');
});
