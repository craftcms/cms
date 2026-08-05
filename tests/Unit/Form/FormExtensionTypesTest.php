<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\Address;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Color;
use CraftCms\Cms\Form\Controls\Date;
use CraftCms\Cms\Form\Controls\ElementSelect;
use CraftCms\Cms\Form\Controls\IconPicker;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Link;
use CraftCms\Cms\Form\Controls\Markdown;
use CraftCms\Cms\Form\Controls\Money;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Controls\Range;
use CraftCms\Cms\Form\Controls\Select;
use CraftCms\Cms\Form\Controls\Table;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Controls\Textarea;
use CraftCms\Cms\Form\Controls\Time;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormControlTypes;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormNodeTypes;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\NodePayload;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\Group;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\Form\Controls\Slug;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\Form\Nodes\Notice;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use Symfony\Component\DomCrawler\Crawler;

it('registers core and plugin Node and Control types separately', function () {
    $nodeTypes = app(FormNodeTypes::class);
    $controlTypes = app(FormControlTypes::class);

    expect($nodeTypes->types()->all())->toBe([Field::class, Group::class])
        ->and($controlTypes->types()->all())->toBe([
            Address::class,
            Choice::class,
            Color::class,
            Date::class,
            ElementSelect::class,
            IconPicker::class,
            Lightswitch::class,
            Link::class,
            Markdown::class,
            Money::class,
            Number::class,
            Range::class,
            Select::class,
            Table::class,
            Text::class,
            Textarea::class,
            Time::class,
        ]);

    new TestPlugin(app())->registerFormTypes($nodeTypes, $controlTypes);

    expect($nodeTypes->types()->all())->toBe([Field::class, Group::class, Notice::class])
        ->and($controlTypes->types()->last())->toBe(Slug::class)
        ->and(fn () => $nodeTypes->register(Slug::class))->toThrow(InvalidArgumentException::class, Node::class)
        ->and(fn () => $controlTypes->register(Notice::class))->toThrow(InvalidArgumentException::class, Control::class);
});

it('renders and submits test plugin types through the PHP renderer', function () {
    new TestPlugin(app())->registerFormTypes(app(FormNodeTypes::class), app(FormControlTypes::class));

    $payload = app(FormResolver::class)->resolve(Form::make([
        new Notice('plugin-notice', 'Provided by the test plugin'),
        Field::make()->label('Slug')->control(Slug::make('slug')->value('custom-value')),
    ]), new FormContext(namespace: 'settings'));
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));

    expect($crawler->filter('[data-test-plugin-notice]')->text())->toBe('Provided by the test plugin')
        ->and($crawler->filter('[data-test-plugin-control][name="settings[slug]"][value="custom-value"][placeholder="plugin-slug"]'))->toHaveCount(1)
        ->and($payload->nodes[0]->props)->toBe(['message' => 'Provided by the test plugin'])
        ->and($payload->nodes[1]->control?->props)->toBe(['placeholder' => 'plugin-slug']);
});

it('rejects unregistered live types and non-JSON-safe properties', function () {
    $notice = new Notice('plugin-notice', 'Unregistered');

    expect(fn () => app(FormResolver::class)->resolve(Form::make([$notice]), new FormContext))
        ->toThrow(InvalidArgumentException::class, Notice::class);

    expect(fn () => app(FormResolver::class)->resolve(Form::make([
        Field::make()->control(Slug::make('slug')),
    ]), new FormContext))
        ->toThrow(InvalidArgumentException::class, Slug::class);

    app(FormNodeTypes::class)->register(Notice::class);
    $invalid = new class('invalid', 'message') extends Notice
    {
        public function props(): array
        {
            return ['callback' => fn () => null];
        }
    };
    app(FormNodeTypes::class)->register($invalid::class);

    expect(fn () => app(FormResolver::class)->resolve(Form::make([$invalid]), new FormContext))
        ->toThrow(InvalidArgumentException::class, 'JSON-safe');

    $invalidFloat = new class('invalid-float', 'message') extends Notice
    {
        public function props(): array
        {
            return ['number' => NAN];
        }
    };
    app(FormNodeTypes::class)->register($invalidFloat::class);

    expect(fn () => app(FormResolver::class)->resolve(Form::make([$invalidFloat]), new FormContext))
        ->toThrow(InvalidArgumentException::class, 'JSON-safe');
});

it('rejects registered renderer exceptions without returning a partial PHP form', function () {
    $broken = new class('broken', 'message') extends Notice
    {
        public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
        {
            throw new RuntimeException('Test plugin renderer failed.');
        }
    };
    app(FormNodeTypes::class)->register($broken::class);
    $payload = app(FormResolver::class)->resolve(Form::make([$broken]), new FormContext);
    $type = $broken::class;

    expect(fn () => app(FormHtmlRenderer::class)->render($payload))
        ->toThrow(RuntimeException::class, "Failed to render Form Node [{$type}] with component [test-plugin:notice] at [broken]: Test plugin renderer failed.");
});

it('reports extension context for invalid definitions', function () {
    new TestPlugin(app())->registerFormTypes(app(FormNodeTypes::class), app(FormControlTypes::class));

    expect(fn () => app(FormResolver::class)->resolve(Form::make([
        new Notice('', 'Missing identity'),
    ]), new FormContext))
        ->toThrow(InvalidArgumentException::class, 'with component [test-plugin:notice] at [unknown] requires a stable UID')
        ->and(fn () => app(FormResolver::class)->resolve(Form::make([
            Field::make()->control(Slug::make([])),
        ]), new FormContext))
        ->toThrow(InvalidArgumentException::class, 'requires a path; component [test-plugin:slug], identity [unknown]')
        ->and(fn () => app(FormResolver::class)->resolve(Form::make([
            Field::make()->control(Slug::make([''])),
        ]), new FormContext))
        ->toThrow(InvalidArgumentException::class, 'Form Control ['.Slug::class.'] with component [test-plugin:slug] at [unknown] paths must contain non-empty string segments');
});
