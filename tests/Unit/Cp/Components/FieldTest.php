<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Field;
use Illuminate\Support\HtmlString;
use Twig\Markup;

describe('attributes', function () {
    it('renders the tag with label and boolean attributes', function () {
        $html = Field::make()
            ->label('My Label')
            ->required()
            ->translatable(true, 'Translates per site')
            ->toHtml();

        expect($html)->toStartWith('<craft-field')
            ->and($html)->toContain('label="My Label"')
            ->and($html)->toContain(' required')
            ->and($html)->toContain(' translatable')
            ->and($html)->toContain('translation-description="Translates per site"')
            ->and($html)->not->toContain('fieldset')
            ->and($html)->not->toContain('has-errors');
    });

    it('renders fieldset, read-only, status, orientation, and id', function () {
        $html = Field::make()
            ->id('my-field')
            ->fieldset()
            ->readOnly()
            ->status('modified', 'This field was modified')
            ->orientation('rtl')
            ->toHtml();

        expect($html)->toContain('id="my-field"')
            ->and($html)->toContain(' fieldset')
            ->and($html)->toContain(' readonly')
            ->and($html)->toContain('status="modified"')
            ->and($html)->toContain('status-label="This field was modified"')
            ->and($html)->toContain('orientation="rtl"');
    });

    it('only renders width when set', function () {
        expect(Field::make()->toHtml())
            ->not->toContain('width');

        expect(Field::make()->width('auto')->toHtml())
            ->toContain('width="auto"');
    });

    it('only renders instructions-position when after', function () {
        expect(Field::make()->instructions('Hi')->toHtml())
            ->not->toContain('instructions-position');

        expect(Field::make()->instructions('Hi')->instructionsPosition('after')->toHtml())
            ->toContain('instructions-position="after"');
    });

    it('merges additional host attributes', function () {
        $html = Field::make()
            ->attributes(['class' => 'a', 'data-foo' => '1'])
            ->attributes(['class' => 'b'])
            ->toHtml();

        expect($html)->toContain('class="a b"')
            ->and($html)->toContain('data-foo="1"');
    });
});

describe('slots', function () {
    it('applies the input slot to a single root element', function () {
        $html = Field::make()->input('<input type="text" name="foo">')->toHtml();

        expect($html)->toContainTag('input', ['type' => 'text', 'name' => 'foo', 'slot' => 'input']);
    });

    it('wraps non-element input content in a slotted span', function () {
        $html = Field::make()->input('plain text')->toHtml();

        expect($html)->toContain('<span slot="input">plain text</span>');
    });

    it('renders a nested component into the input slot', function () {
        $html = Field::make()
            ->input(Field::make()->label('Inner'))
            ->toHtml();

        expect($html)->toContain('<craft-field slot="input" label="Inner">');
    });

    it('renders markdown instructions into the help-text slot', function () {
        $html = Field::make()->instructions('Some **bold** text')->toHtml();

        expect($html)->toContain('slot="help-text"')
            ->and($html)->toContain('<strong>bold</strong>');
    });

    it('renders tip and warning slots with inline markdown', function () {
        $html = Field::make()->tip('A *tip*')->warning('A warning')->toHtml();

        expect($html)->toContain('slot="tip"')
            ->and($html)->toContain('<em>tip</em>')
            ->and($html)->toContain('slot="warning"');
    });

    it('renders an Htmlable label into the label slot instead of the attribute', function () {
        $html = Field::make()->label(new HtmlString('<span>Fancy</span>'))->toHtml();

        expect($html)->toContain('<span slot="label">Fancy</span>')
            ->and($html)->not->toContain('label="');
    });

    it('renders a Twig Markup label into the label slot instead of the attribute', function () {
        $html = Field::make()->label(new Markup('<b>All</b>', 'UTF-8'))->toHtml();

        expect($html)->toContain('<b slot="label">All</b>')
            ->and($html)->not->toContain('label="');
    });

    it('renders label-extra content', function () {
        $html = Field::make()->labelExtra('<button type="button">Copy</button>')->toHtml();

        expect($html)->toContain('<button type="button" slot="label-extra">Copy</button>');
    });
});

describe('errors', function () {
    it('renders an escaped error list into the feedback slot and flags has-errors', function () {
        $html = Field::make()->errors(['Bad one', 'Bad <two>'])->toHtml();

        expect($html)->toContain(' has-errors')
            ->and($html)->toContain('slot="feedback"')
            ->and($html)->toContain('class="error-list"')
            ->and($html)->toContain('<li>Bad one</li>')
            ->and($html)->toContain('<li>Bad &lt;two&gt;</li>');
    });

    it('ignores empty error arrays', function () {
        $html = Field::make()->errors([])->toHtml();

        expect($html)->not->toContain('has-errors')
            ->and($html)->not->toContain('slot="feedback"');
    });
});

it('casts to string and Htmlable', function () {
    $field = Field::make()->label('Cast');

    expect((string) $field)->toBe($field->toHtml())
        ->and($field->toHtml())->toContain('label="Cast"');
});
