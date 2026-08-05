<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Legacy;

use craft\base\Event as YiiEvent;
use craft\base\FieldLayoutElement as LegacyFieldLayoutElement;
use craft\models\FieldLayout as LegacyFieldLayout;
use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormControlTypes;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormNodeTypes;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Yii2Adapter\Form\Controls\LegacyHtmlControl;
use CraftCms\Yii2Adapter\Form\Enums\LegacyHtmlMode;
use CraftCms\Yii2Adapter\Form\LegacyHtml;
use CraftCms\Yii2Adapter\Form\Nodes\LegacyHtmlField;
use Mockery;
use Override;
use Symfony\Component\DomCrawler\Crawler;

class LegacyHookField extends PlainText
{
    #[Override]
    public function getInputHtml(mixed $value, ?ElementInterface $element): string
    {
        return '<input name="value" value="editable">';
    }

    #[Override]
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return '<span data-static>static</span>';
    }
}

function legacyHookLayoutElement(string $uid = 'legacy-element'): LegacyFieldLayoutElement
{
    if (!class_exists(LegacyHookLayoutElement::class, false)) {
        class LegacyHookLayoutElement extends LegacyFieldLayoutElement
        {
            #[Override]
            public function selectorHtml(): string
            {
                return 'Legacy element';
            }

            #[Override]
            public function formHtml(?ElementInterface $element = null, bool $static = false): string
            {
                HtmlStack::css('.legacy-layout { color: red; }');
                $titleId = InputNamespace::namespaceId('title');
                HtmlStack::js("window.legacyLayout = '#{$titleId}';");

                return sprintf(
                    '<input id="title" name="title" value="Draft"><input name="meta[slug]" value="draft"><span data-static="%s"></span>',
                    $static ? 'true' : 'false',
                );
            }
        }
    }

    return new LegacyHookLayoutElement(['uid' => $uid]);
}

it('registers its private Form types', function() {
    expect(app(FormNodeTypes::class)->types()->contains(LegacyHtmlField::class))->toBeTrue()
        ->and(app(FormControlTypes::class)->types()->contains(LegacyHtmlControl::class))->toBeTrue();
});

it('eagerly captures namespaced HTML and assets into a JSON-safe payload', function() {
    $component = Mockery::mock(ConfigurableComponentInterface::class);
    $component->expects('getSettingsHtml')->andReturnUsing(function(): string {
        HtmlStack::css('.legacy-field { color: red; }');
        HtmlStack::js("window.legacyField = '#" . InputNamespace::namespaceId('title') . "';");

        return '<input id="title" name="title" value="Draft">';
    });

    $node = app(LegacyHtml::class)->settings(
        component: $component,
        path: '__legacy',
        namespace: 'settings',
    );
    $payload = app(FormResolver::class)->resolve(Form::make([$node]), new FormContext());
    $control = $payload->nodes[0]->control;

    expect($control?->props['namespace'])->toBe('settings')
        ->and($control?->props['fragment']['html'])->toBe('<input id="settings-title" name="settings[title]" value="Draft">')
        ->and($control?->props['fragment']['headHtml'])->toContain('.legacy-field { color: red; }')
        ->and($control?->props['fragment']['bodyHtml'])->toContain("window.legacyField = '#settings-title'")
        ->and($payload->values)->toBe(['__legacy' => ['settings[title]' => 'Draft']])
        ->and(json_encode($payload, JSON_THROW_ON_ERROR))->toBeString();
});

it('composes an explicit namespace with the active namespace', function() {
    $component = Mockery::mock(ConfigurableComponentInterface::class);
    $component->expects('getSettingsHtml')->andReturnUsing(fn(): string => sprintf(
        '<input id="title" name="title"><script data-namespace="%s"></script>',
        InputNamespace::get(),
    ));

    $node = InputNamespace::with('outer', fn() => app(LegacyHtml::class)->settings(
        component: $component,
        path: '__legacy',
        namespace: 'settings',
    ));
    $payload = app(FormResolver::class)->resolve(Form::make([$node]), new FormContext());
    $html = $payload->nodes[0]->control?->props['fragment']['html'];

    expect($html)->toContain('name="outer[settings][title]"')
        ->and($html)->toContain('id="outer-settings-title"')
        ->and($html)->toContain('data-namespace="outer[settings]"');
});

it('round-trips zero, one, and multiple named roots through a flat map', function(string $html, array $flat, array $expanded) {
    $legacyHtml = app(LegacyHtml::class);

    expect($legacyHtml->parse($html))->toBe($flat)
        ->and($legacyHtml->expand($flat))->toBe($expanded);
})->with([
    'zero roots' => ['<p>No settings</p>', [], []],
    'one root' => [
        '<input name="settings[apiKey]" value="secret">',
        ['settings[apiKey]' => 'secret'],
        ['settings' => ['apiKey' => 'secret']],
    ],
    'multiple roots and repeated values' => [
        '<input name="settings[apiKey]" value="secret"><input type="checkbox" name="features[]" value="sync" checked><input type="checkbox" name="features[]" value="drafts" checked><input name="ignored" value="no" disabled>',
        ['settings[apiKey]' => 'secret', 'features[]' => ['sync', 'drafts']],
        ['settings' => ['apiKey' => 'secret'], 'features' => ['sync', 'drafts']],
    ],
]);

it('maps legacy field modes to their established hooks', function(LegacyHtmlMode $mode, string $expected, ControlMode $controlMode) {
    $field = new LegacyHookField();
    $element = Mockery::mock(Entry::class);

    $node = app(LegacyHtml::class)->field(
        field: $field,
        value: 'value',
        element: $element,
        path: '__legacy',
        namespace: 'fields[example]',
        mode: $mode,
    );
    $payload = app(FormResolver::class)->resolve(Form::make([$node]), new FormContext());
    $control = $payload->nodes[0]->control;
    $html = $control?->props['fragment']['html'];

    expect($control?->mode)->toBe($controlMode)
        ->and($html)->toContain($expected);

    if ($mode === LegacyHtmlMode::ReadOnly || $mode === LegacyHtmlMode::Disabled) {
        expect(new Crawler($html))->filter('input[disabled]')->toHaveCount(1);
    }
})->with([
    'editable' => [LegacyHtmlMode::Editable, 'editable', ControlMode::Editable],
    'static' => [LegacyHtmlMode::Static, 'data-static', ControlMode::ReadOnly],
    'read-only' => [LegacyHtmlMode::ReadOnly, 'editable', ControlMode::ReadOnly],
    'disabled' => [LegacyHtmlMode::Disabled, 'editable', ControlMode::Disabled],
]);

it('omits null hooks and reports capture failures', function() {
    $legacyHtml = app(LegacyHtml::class);

    expect($legacyHtml->capture('__legacy', fn(): null => null))->toBeNull()
        ->and(fn() => $legacyHtml->capture('__legacy', fn() => throw new \RuntimeException('plugin failed')))
        ->toThrow(\RuntimeException::class, 'plugin failed');
});

it('renders the same captured fragment through PHP and restores its assets to the page stack', function() {
    $node = app(LegacyHtml::class)->capture(
        path: '__legacy',
        hook: function(): string {
            HtmlStack::html('<meta name="legacy-head">', Position::Head);
            HtmlStack::html('<script>window.legacyBody = true</script>', Position::BodyEnd);

            return '<input name="settings[value]" value="rendered">';
        },
    );
    $payload = app(FormResolver::class)->resolve(Form::make([$node]), new FormContext());
    $html = app(FormHtmlRenderer::class)->render($payload);

    expect($html)->toContain('name="settings[value]"')
        ->and(HtmlStack::headHtml())->toContain('name="legacy-head"')
        ->and(HtmlStack::bodyHtml())->toContain('window.legacyBody = true');
});

it('compiles legacy FieldLayout elements into namespaced multi-root HTML islands', function() {
    $layoutElement = legacyHookLayoutElement();
    $layout = FieldLayout::make(Entry::class)
        ->tab('Content', fn(FieldLayoutTab $tab) => $tab->add($layoutElement));
    $layout->getTabs()[0]->uid = 'content-tab';
    $config = $layout->getConfig();

    $payload = app(FieldLayoutCompiler::class)->compile(
        $layout,
        context: new FormContext(
            namespace: ['nested', 'block'],
            errors: ['__legacyFieldLayout.legacy-element.title' => 'Title is invalid.'],
            refreshable: true,
        ),
    );
    $control = $payload->nodes[0]->children[0]->control;
    $html = $control?->props['fragment']['html'];
    $fragment = new Crawler($html);

    expect($payload->refreshable)->toBeTrue()
        ->and($control?->path)->toBe(['nested', 'block', '__legacyFieldLayout', 'legacy-element'])
        ->and($fragment->filter('#nested-block-title'))->toHaveCount(1)
        ->and($fragment->filter('input[name="nested[block][title]"]')->attr('value'))->toBe('Draft')
        ->and($fragment->filter('input[name="nested[block][meta][slug]"]')->attr('value'))->toBe('draft')
        ->and($control?->props['fragment']['headHtml'])->toContain('.legacy-layout { color: red; }')
        ->and($control?->props['fragment']['bodyHtml'])->toContain("window.legacyLayout = '#nested-block-title'")
        ->and($payload->errors)->toBe([[
            'path' => ['nested', 'block', '__legacyFieldLayout', 'legacy-element'],
            'messages' => ['Title is invalid.'],
        ]])
        ->and($layoutElement->uid)->toBe('legacy-element')
        ->and($layout->getConfig())->toBe($config)
        ->and($payload->values)->toBe([
            'nested' => [
                'block' => [
                    '__legacyFieldLayout' => [
                        'legacy-element' => [
                            'nested[block][title]' => 'Draft',
                            'nested[block][meta][slug]' => 'draft',
                        ],
                    ],
                ],
            ],
        ]);

    $rendered = new Crawler(app(FormHtmlRenderer::class)->render($payload));

    expect($rendered->filter('input[name="nested[block][title]"]'))->toHaveCount(1)
        ->and($rendered->filter('input[name="nested[block][meta][slug]"]'))->toHaveCount(1)
        ->and($rendered->filter('[aria-invalid="true"] .error-list')->text())->toContain('Title is invalid.');
});

it('maps Form modes onto legacy FieldLayout HTML', function(ControlMode $mode, bool $static) {
    $layout = FieldLayout::make(Entry::class)
        ->tab('Content', fn(FieldLayoutTab $tab) => $tab->add(legacyHookLayoutElement()));
    $layout->getTabs()[0]->uid = 'content-tab';

    $payload = app(FieldLayoutCompiler::class)->compile(
        $layout,
        context: new FormContext(mode: $mode),
    );
    $control = $payload->nodes[0]->children[0]->control;

    expect($control?->mode)->toBe($mode)
        ->and($control?->props['fragment']['html'])->toContain(sprintf(
            'data-static="%s"',
            $static ? 'true' : 'false',
        ));

    if ($mode === ControlMode::Disabled) {
        expect(new Crawler($control?->props['fragment']['html']))
            ->filter('input[disabled]')->toHaveCount(2);
    }
})->with([
    'editable' => [ControlMode::Editable, false],
    'read-only' => [ControlMode::ReadOnly, true],
    'disabled' => [ControlMode::Disabled, true],
]);

it('translates legacy FieldLayout tab order and static mode mutations', function() {
    $layout = FieldLayout::make(Entry::class)
        ->tab('First', fn(FieldLayoutTab $tab) => $tab->add(legacyHookLayoutElement('first-element')))
        ->tab('Second', fn(FieldLayoutTab $tab) => $tab->add(legacyHookLayoutElement('second-element')));
    $layout->getTabs()[0]->uid = 'first-tab';
    $layout->getTabs()[1]->uid = 'second-tab';

    $dispatches = 0;
    YiiEvent::on(LegacyFieldLayout::class, LegacyFieldLayout::EVENT_CREATE_FORM, function($event) use (&$dispatches) {
        $dispatches++;
        $event->tabs = array_reverse($event->tabs);
        $event->tabs[] = new FieldLayoutTab([
            'layout' => $event->sender,
            'name' => 'Added',
            'elements' => [legacyHookLayoutElement('added-element')],
        ]);
        $event->static = true;
    });

    try {
        $payload = app(FieldLayoutCompiler::class)->compile($layout);
    } finally {
        YiiEvent::off(LegacyFieldLayout::class, LegacyFieldLayout::EVENT_CREATE_FORM);
    }

    expect(array_column($payload->nodes, 'uid'))->toBe(['second-tab', 'first-tab', 'yii2-adapter:event-tab:2'])
        ->and($dispatches)->toBe(1)
        ->and($payload->nodes[2]->component)->toBe('craft:tab')
        ->and($payload->nodes[0]->children[0]->control?->mode)->toBe(ControlMode::ReadOnly)
        ->and($payload->nodes[0]->children[0]->control?->props['fragment']['html'])->toContain('data-static="true"')
        ->and($payload->nodes[2]->children[0]->control?->path)->toBe(['__legacyFieldLayout', 'added-element']);
});

it('rejects legacy mutations of removed FieldLayout renderer bookkeeping', function() {
    $layout = FieldLayout::make(Entry::class)
        ->tab('Content', fn(FieldLayoutTab $tab) => $tab->add(legacyHookLayoutElement()));
    $layout->getTabs()[0]->uid = 'content-tab';

    YiiEvent::on(LegacyFieldLayout::class, LegacyFieldLayout::EVENT_CREATE_FORM, function($event) {
        $event->form->tabIdPrefix = 'plugin';
    });

    try {
        expect(fn() => app(FieldLayoutCompiler::class)->compile($layout))
            ->toThrow(\RuntimeException::class, 'form.tabIdPrefix');
    } finally {
        YiiEvent::off(LegacyFieldLayout::class, LegacyFieldLayout::EVENT_CREATE_FORM);
    }
});
