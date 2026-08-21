<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Legacy;

use craft\base\ConfigurableComponent;
use craft\base\ConfigurableComponentInterface;
use craft\base\Event as YiiEvent;
use craft\base\FieldInterface;
use craft\base\FieldLayoutElement as LegacyFieldLayoutElement;
use craft\base\Plugin as LegacyPlugin;
use craft\fields\Addresses;
use craft\fields\Assets;
use craft\fields\BaseOptionsField;
use craft\fields\BaseRelationField;
use craft\fields\ButtonGroup;
use craft\fields\Categories;
use craft\fields\Checkboxes;
use craft\fields\Color;
use craft\fields\ContentBlock;
use craft\fields\Country;
use craft\fields\Date;
use craft\fields\Dropdown;
use craft\fields\Email;
use craft\fields\Entries;
use craft\fields\Icon;
use craft\fields\Json;
use craft\fields\Lightswitch;
use craft\fields\Link;
use craft\fields\Matrix;
use craft\fields\MissingField;
use craft\fields\Money;
use craft\fields\MultiSelect;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\fields\RadioButtons;
use craft\fields\Range;
use craft\fields\Table;
use craft\fields\Tags;
use craft\fields\Time;
use craft\fields\Url;
use craft\fields\Users;
use craft\models\FieldLayout as LegacyFieldLayout;
use craft\models\FieldLayoutForm;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\FieldContext;
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
use CraftCms\Yii2Adapter\Field\Field as LegacyField;
use CraftCms\Yii2Adapter\Form\Contracts\LegacySettingsComponent as LegacySettingsContract;
use CraftCms\Yii2Adapter\Form\Controls\LegacyHtmlControl;
use CraftCms\Yii2Adapter\Form\Enums\LegacyHtmlMode;
use CraftCms\Yii2Adapter\Form\LegacyHtml;
use CraftCms\Yii2Adapter\Form\Nodes\LegacyHtmlField;
use Mockery;
use Override;
use Symfony\Component\DomCrawler\Crawler;

class LegacyHookField extends LegacyField
{
    #[Override]
    public function getInputHtml(mixed $value, ?ElementInterface $element): string
    {
        return sprintf('<input name="%s" value="editable">', $this->handle);
    }

    #[Override]
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return '<span data-static>static</span>';
    }
}

class LegacySettingsComponent extends ConfigurableComponent
{
    #[Override]
    public function getSettingsHtml(): string
    {
        return '<input name="apiKey" value="secret">';
    }
}

class LegacySettingsPlugin extends LegacyPlugin
{
    #[Override]
    protected function settingsHtml(): string
    {
        return '<input name="apiKey" value="secret">';
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
    $component = Mockery::mock(LegacySettingsContract::class);
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
    $component = Mockery::mock(LegacySettingsContract::class);
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

it('implements replacement Form operations through legacy hooks', function() {
    $settings = new LegacySettingsComponent()->settingsForm(new FormContext(
        namespace: 'settings',
        mode: ControlMode::ReadOnly,
    ));
    $settingsPayload = app(FormResolver::class)->resolve($settings, new FormContext(namespace: 'settings'));
    $fieldControl = new LegacyHookField(['handle' => 'legacy'])->formControl(new FieldContext(
        path: ['fields', 'legacy'],
        value: 'value',
        element: Mockery::mock(Entry::class),
        form: new FormContext(namespace: ['nested', 'block']),
        mode: ControlMode::Disabled,
    ));

    expect($settingsPayload->nodes[0]->control?->mode)->toBe(ControlMode::ReadOnly)
        ->and($settingsPayload->nodes[0]->control?->props['fragment']['html'])
        ->toContain('name="settings[apiKey]"', 'disabled')
        ->and($fieldControl)->toBeInstanceOf(LegacyHtmlControl::class)
        ->and($fieldControl->props()['fragment']['html'])
        ->toContain('name="nested[block][fields][legacy]"', 'disabled');
});

it('wraps legacy plugin settings HTML in a Form', function(ControlMode $mode, bool $disabled) {
    $context = new FormContext(namespace: 'settings', mode: $mode);
    $form = new LegacySettingsPlugin('legacy-settings')->settingsForm($context);
    $payload = app(FormResolver::class)->resolve($form, $context);
    $control = $payload->nodes[0]->control;
    $input = new Crawler($control?->props['fragment']['html']);

    expect($control?->path)->toBe(['settings', '__legacySettings'])
        ->and($control?->mode)->toBe($mode)
        ->and($control?->deltaGroup)->toBe(['settings'])
        ->and($control?->props['expandValues'])->toBeTrue()
        ->and($input->filter('input[name="settings[apiKey]"]'))->toHaveCount(1)
        ->and($input->filter('input[disabled]')->count() === 1)->toBe($disabled);
})->with([
    'editable' => [ControlMode::Editable, false],
    'read-only' => [ControlMode::ReadOnly, true],
]);

it('preserves legacy hooks on public field aliases', function() {
    if (!class_exists(LegacyPlainTextField::class, false)) {
        class LegacyPlainTextField extends PlainText
        {
            public function getInputHtml(mixed $value, ?ElementInterface $element): string
            {
                return sprintf('<input name="%s" value="built-in-alias">', $this->handle);
            }
        }
    }

    $field = new LegacyPlainTextField(['handle' => 'legacy']);
    $control = $field->formControl(new FieldContext(
        path: ['fields', 'legacy'],
        value: 'value',
        element: Mockery::mock(Entry::class),
        form: new FormContext(),
    ));

    expect(method_exists(ConfigurableComponentInterface::class, 'getSettingsHtml'))->toBeTrue()
        ->and(method_exists(FieldInterface::class, 'getInputHtml'))->toBeTrue()
        ->and(method_exists(PlainText::class, 'getSettingsHtml'))->toBeTrue()
        ->and($control)->toBeInstanceOf(LegacyHtmlControl::class)
        ->and($control->props()['fragment']['html'])->toContain('built-in-alias');
});

it('preserves legacy hooks on each built-in field alias', function(string $fieldClass) {
    expect(method_exists($fieldClass, 'getSettingsHtml'))->toBeTrue()
        ->and(method_exists($fieldClass, 'getInputHtml'))->toBeTrue()
        ->and(method_exists($fieldClass, 'getStaticHtml'))->toBeTrue();
})->with([
    'addresses' => Addresses::class,
    'assets' => Assets::class,
    'base options field' => BaseOptionsField::class,
    'base relation field' => BaseRelationField::class,
    'button group' => ButtonGroup::class,
    'categories' => Categories::class,
    'checkboxes' => Checkboxes::class,
    'color' => Color::class,
    'content block' => ContentBlock::class,
    'country' => Country::class,
    'date' => Date::class,
    'dropdown' => Dropdown::class,
    'email' => Email::class,
    'entries' => Entries::class,
    'icon' => Icon::class,
    'json' => Json::class,
    'lightswitch' => Lightswitch::class,
    'link' => Link::class,
    'matrix' => Matrix::class,
    'missing field' => MissingField::class,
    'money' => Money::class,
    'multi-select' => MultiSelect::class,
    'number' => Number::class,
    'plain text' => PlainText::class,
    'radio buttons' => RadioButtons::class,
    'range' => Range::class,
    'table' => Table::class,
    'tags' => Tags::class,
    'time' => Time::class,
    'url' => Url::class,
    'users' => Users::class,
]);

it('preserves nullable settings and renderer-native static output on built-in aliases', function() {
    expect(new MissingField()->settingsForm())->toBeNull()
        ->and(new MissingField()->getSettingsHtml())->toBeNull();

    $html = new Matrix(['handle' => 'matrix'])->getStaticHtml(null, Mockery::mock(Entry::class));

    expect($html)->toContain('craft-matrix-input')
        ->not->toContain('data-form-matrix-add');
});

it('preserves FieldLayout createForm on its public alias', function() {
    $layout = LegacyFieldLayout::make(Entry::class);

    expect(method_exists($layout, 'createForm'))->toBeTrue()
        ->and($layout->createForm())->toBeInstanceOf(FieldLayoutForm::class);
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
