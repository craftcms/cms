<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Callout;
use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\MissingComponent;
use CraftCms\Cms\Cp\Components\ViewComponent;
use CraftCms\Cms\View\TemplateMode;
use Twig\Markup;

use function CraftCms\Cms\ui;

describe('configure', function () {
    it('applies config keys via fluent setters, camelizing kebab and snake keys', function () {
        $html = Callout::make()
            ->configure([
                'variant' => 'warning',
                'title' => 'Heads up',
                'inline' => true,
            ])
            ->toHtml();

        expect($html)->toContain('variant="warning"')
            ->and($html)->toContain('title="Heads up"')
            ->and($html)->toContain(' inline');

        expect(Field::make()->configure(['instructions-position' => 'after'])->toHtml())
            ->toContain('instructions-position="after"');

        expect(Field::make()->configure(['read_only' => true])->toHtml())
            ->toContain(' readonly');
    });

    it('throws for unknown config keys', function () {
        expect(fn () => Callout::make()->configure(['nope' => 1]))
            ->toThrow(InvalidArgumentException::class, 'nope');
    });

    it('rejects getter and rendering method names as config keys', function (string $key) {
        expect(fn () => Callout::make()->configure([$key => 'x']))
            ->toThrow(InvalidArgumentException::class, $key);
    })->with(['getVariant', 'isDisabled', 'toHtml', 'renderSlots']);
});

describe('ui()', function () {
    it('creates a configured component by name', function () {
        $component = ui('callout', ['variant' => 'info', 'content' => 'Hi']);

        expect($component)->toBeInstanceOf(Callout::class)
            ->and($component->toHtml())->toContain('variant="info"')
            ->and($component->toHtml())->toContain('Hi');
    });

    it('creates missing component placeholders with slots', function () {
        $component = ui('missing-component', [
            'error' => 'Plugin disabled.',
            'pluginName' => 'Example',
            'action' => new Markup('<button>Enable</button>', 'UTF-8'),
        ]);

        expect($component)->toBeInstanceOf(MissingComponent::class)
            ->and($component->toHtml())->toContain('error="Plugin disabled."')
            ->and($component->toHtml())->toContain('plugin-name="Example"')
            ->and($component->toHtml())->toContain('<button slot="action">Enable</button>');
    });

    it('remains chainable after creation', function () {
        expect(ui('callout')->variant('danger')->toHtml())
            ->toContain('variant="danger"');
    });

    it('throws for unknown component names', function () {
        expect(fn () => ui('nope'))
            ->toThrow(InvalidArgumentException::class, 'Unknown UI component');
    });

    it('supports registering additional components', function () {
        $registry = app(ComponentRegistry::class);
        $registry->register('aliased-callout', Callout::class);

        expect(ui('aliased-callout'))->toBeInstanceOf(Callout::class);
    });

    it('rejects registering non-components', function () {
        expect(fn () => app(ComponentRegistry::class)->register('bad', stdClass::class))
            ->toThrow(InvalidArgumentException::class, ViewComponent::class);
    });
});

describe('twig integration', function () {
    it('renders through the ui() twig function unescaped', function () {
        $html = CraftCms\Cms\renderString(
            "{{ ui('callout', {variant: 'warning', content: 'Careful!'}) }}",
            templateMode: TemplateMode::Cp,
        );

        expect($html)->toContain('<craft-callout')
            ->and($html)->toContain('variant="warning"')
            ->and($html)->toContain('Careful!');
    });

    it('treats twig markup content as pre-escaped html', function () {
        $component = ui('callout', [
            'content' => new Markup('<em>safe</em>', 'UTF-8'),
        ]);

        expect($component->toHtml())->toContain('<em>safe</em>');
    });

    it('still encodes plain twig strings', function () {
        $html = CraftCms\Cms\renderString(
            "{{ ui('callout', {content: '<em>unsafe</em>'}) }}",
            templateMode: TemplateMode::Cp,
        );

        expect($html)->toContain('&lt;em&gt;unsafe&lt;/em&gt;');
    });
});
