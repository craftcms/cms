<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Icon;
use CraftCms\Cms\Shared\Enums\Color;
use Illuminate\Support\HtmlString;

describe('name resolution', function () {
    it('renders the resolved name and family', function () {
        $html = Icon::make()->name('gear')->toHtml();

        expect($html)->toContain('name="gear"')
            ->and($html)->toContain('family=');
    });

    it('resolves legacy icon aliases', function () {
        expect(Icon::make()->name('settings')->toHtml())->toContain('name="gear"')
            ->and(Icon::make()->name('edit')->toHtml())->toContain('name="pencil"');
    });

    it('resolves custom icons to the custom-icons family', function () {
        $html = Icon::make()->name('move')->toHtml();

        expect($html)->toContain('name="grip-dots"')
            ->and($html)->toContain('family="custom-icons"');
    });

    it('lets an explicit family override the resolved one', function () {
        $html = Icon::make()->name('move')->family('classic')->toHtml();

        expect($html)->toContain('family="classic"')
            ->and($html)->not->toContain('custom-icons');
    });

    it('renders no icon attributes without a name', function () {
        expect(Icon::make()->toHtml())->toBe('<craft-icon></craft-icon>');
    });
});

describe('host attributes', function () {
    it('renders variant, label and appearance', function () {
        $html = Icon::make()
            ->name('gear')
            ->variant('regular')
            ->label('Settings')
            ->appearance('badge')
            ->toHtml();

        expect($html)->toContain('variant="regular"')
            ->and($html)->toContain('label="Settings"')
            ->and($html)->toContain('appearance="badge"');
    });

    it('renders the color as a data-color palette attribute', function () {
        expect(Icon::make()->name('pencil')->color(Color::Teal)->toHtml())
            ->toContain('data-color="teal"')
            ->and(Icon::make()->name('pencil')->color('rose')->toHtml())
            ->toContain('data-color="rose"');
    });

    it('merges additional host attributes', function () {
        $html = Icon::make()->name('gear')->attributes(['class' => 'cp-icon puny'])->toHtml();

        expect($html)->toContain('class="cp-icon puny"');
    });
});

describe('inline svg', function () {
    it('renders trusted svg markup in the default slot', function () {
        $html = Icon::make()->svg('<svg viewBox="0 0 10 10"></svg>')->toHtml();

        expect($html)->toContain('<svg viewBox="0 0 10 10"></svg>');
    });

    it('accepts Htmlable svg content', function () {
        $html = Icon::make()->svg(new HtmlString('<svg data-x></svg>'))->toHtml();

        expect($html)->toContain('<svg data-x></svg>');
    });

    it('clears the slot when set back to null', function () {
        expect(Icon::make()->svg('<svg></svg>')->svg(null)->toHtml())
            ->not->toContain('<svg');
    });
});
