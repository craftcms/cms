<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Cp\Enums\Appearance;
use CraftCms\Cms\Cp\Enums\Size;
use CraftCms\Cms\Cp\Enums\Variant;
use Illuminate\Support\HtmlString;

describe('attributes', function () {
    it('renders variant, appearance, size, and icon', function () {
        $html = Button::make()
            ->variant(Variant::Danger)
            ->appearance(Appearance::Outline)
            ->size(Size::Small)
            ->icon('trash')
            ->toHtml();

        expect($html)->toStartWith('<craft-button')
            ->and($html)->toContain('variant="danger"')
            ->and($html)->toContain('appearance="outline"')
            ->and($html)->toContain('size="small"')
            ->and($html)->toContain('icon="trash"');
    });

    it('always renders an explicit type, since the web component defaults to submit', function () {
        expect(Button::make()->toHtml())->toContain('type="button"');

        expect(Button::make()->type('submit')->toHtml())->toContain('type="submit"');
    });

    it('renders state attributes', function () {
        $html = Button::make()->loading()->active()->disabled()->toHtml();

        expect($html)->toContain(' loading')
            ->and($html)->toContain('active="true"')
            ->and($html)->toContain(' disabled');
    });

    it('renders as a link with href, forcing the button type', function () {
        $html = Button::make()
            ->type('submit')
            ->href('https://craftcms.com', '_blank')
            ->toHtml();

        expect($html)->toContain('href="https://craftcms.com"')
            ->and($html)->toContain('target="_blank"')
            ->and($html)->toContain('type="button"');
    });

    it('renders the accessible name for icon-only buttons', function () {
        expect(Button::make()->icon('plus')->accessibleName('Add row')->toHtml())
            ->toContain('accessible-name="Add row"');
    });
});

describe('slots', function () {
    it('encodes plain string labels and trusts Htmlable labels', function () {
        expect(Button::make()->label('Save & close')->toHtml())
            ->toContain('Save &amp; close');

        expect(Button::make()->label(new HtmlString('<em>Fancy</em>'))->toHtml())
            ->toContain('<em>Fancy</em>');
    });

    it('renders prefix and suffix content as trusted markup', function () {
        $html = Button::make()
            ->label('Add')
            ->prefix('<craft-icon name="plus"></craft-icon>')
            ->suffix('<craft-icon name="chevron-down"></craft-icon>')
            ->toHtml();

        expect($html)->toContain('<craft-icon name="plus" slot="prefix">')
            ->and($html)->toContain('<craft-icon name="chevron-down" slot="suffix">');
    });
});

it('evaluates closures with injection', function () {
    $html = Button::make()
        ->label(fn (Button $button): string => 'Lazy')
        ->loading(fn (): bool => true)
        ->toHtml();

    expect($html)->toContain('Lazy')
        ->and($html)->toContain(' loading');
});
