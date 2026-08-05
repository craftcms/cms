<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Legacy;

use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\PlainText;
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
