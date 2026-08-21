<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Input;

describe('input', function () {
    it('renders the slotted native input inside the web component', function () {
        $html = Input::make()
            ->id('site-name')
            ->name('siteName')
            ->value('Happy Lager')
            ->toHtml();

        expect($html)->toContain('<craft-input name="siteName">')
            ->and($html)->toContain('slot="input"')
            ->and($html)->toContain('type="text"')
            ->and($html)->toContain('id="site-name"')
            ->and($html)->toContain('name="siteName"')
            ->and($html)->toContain('value="Happy Lager"')
            ->and($html)->toContain('class="text fullwidth"')
            ->and($html)->toContain('autocomplete="off"')
            ->and($html)->toContain('dir="ltr"');
    });

    it('drops the fullwidth class when a character width is set', function () {
        $html = Input::make()->id('i')->inputSize(10)->toHtml();

        expect($html)->toContain('size="10"')
            ->and($html)->toContain('class="text"')
            ->and($html)->not->toContain('fullwidth');
    });

    it('reflects maxlength on the host and the native input', function () {
        $html = Input::make()->id('i')->maxlength(255)->toHtml();

        expect($html)->toContain('<craft-input maxlength="255">')
            ->and($html)->toContain('maxlength="255" ');
    });

    it('reflects inputmode on the host and the native input', function () {
        $html = Input::make()->id('i')->inputmode('numeric')->toHtml();

        expect($html)->toContain('<craft-input inputmode="numeric">')
            ->and(substr_count($html, 'inputmode="numeric"'))->toBe(2);
    });

    it('maps autocomplete booleans to on/off and passes strings through', function () {
        expect(Input::make()->id('i')->autocomplete(true)->toHtml())->toContain('autocomplete="on"')
            ->and(Input::make()->id('i')->autocomplete('postal-code')->toHtml())->toContain('autocomplete="postal-code"');
    });

    it('renders autocorrect/autocapitalize opt-outs only', function () {
        $default = Input::make()->id('i')->toHtml();
        $optedOut = Input::make()->id('i')->autocorrect(false)->autocapitalize(false)->toHtml();

        expect($default)->not->toContain('autocorrect')
            ->and($default)->not->toContain('autocapitalize')
            ->and($optedOut)->toContain('autocorrect="off"')
            ->and($optedOut)->toContain('autocapitalize="none"');
    });

    it('renders disabled and readonly state', function () {
        $html = Input::make()->id('i')->disabled()->readOnly()->toHtml();

        expect($html)->toContain(' disabled')
            ->and($html)->toContain(' readonly');
    });

    it('omits autofocus without an autofocus-preferring current user', function () {
        expect(Input::make()->id('i')->autofocus()->toHtml())->not->toContain('autofocus');
    });

    it('wires combobox aria state', function () {
        $html = Input::make()->id('i')->role('combobox')->toHtml();

        expect($html)->toContain('role="combobox"')
            ->and($html)->toContain('aria-expanded="false"');
    });

    it('marks the input for the chars-left counter with matching padding', function () {
        $html = Input::make()->id('i')->maxlength(255)->showCharsLeft()->toHtml();

        expect($html)->toContain('data-show-chars-left')
            ->and($html)->toContain('padding-right: 35.6px');
    });

    it('lets inputAttributes override computed defaults like dir', function () {
        $html = Input::make()
            ->id('i')
            ->inputAttributes(['dir' => 'rtl', 'class' => 'extra'])
            ->toHtml();

        expect($html)->toContain('dir="rtl"')
            ->and($html)->toContain('class="text fullwidth extra"');
    });

    it('renders a configured text expander for the native input', function () {
        $html = Input::make()
            ->id('path')
            ->name('path')
            ->textExpanderTriggers([
                [
                    'trigger' => '$',
                    'boundary' => 'start',
                    'options' => [['label' => '$BASE_PATH', 'value' => '$BASE_PATH']],
                ],
            ])
            ->toHtml();

        expect($html)->toContainTag('craft-text-expander', [
            'for' => 'path',
            'triggers' => '[{"trigger":"$","boundary":"start","options":[{"label":"$BASE_PATH","value":"$BASE_PATH"}]}]',
        ]);
    });
});

describe('host attributes', function () {
    it('mirrors Lion-synced control attributes onto the host', function () {
        // Lion pushes type/placeholder/name/disabled/readonly from the host
        // onto the slotted input on upgrade, so non-default values must be
        // reflected there or the server-rendered attributes get clobbered.
        $html = Input::make()
            ->id('i')
            ->type('password')
            ->placeholder('Enter a value')
            ->name('myField')
            ->disabled()
            ->readOnly()
            ->toHtml();

        expect($html)->toMatch('/<craft-input[^>]* type="password"/')
            ->and($html)->toMatch('/<craft-input[^>]* placeholder="Enter a value"/')
            ->and($html)->toMatch('/<craft-input[^>]* name="myField"/')
            ->and($html)->toMatch('/<craft-input[^>]* disabled/')
            ->and($html)->toMatch('/<craft-input[^>]* readonly/');
    });

    it('omits Lion-default control attributes from the host', function () {
        expect(Input::make()->id('i')->toHtml())
            ->toContain('<craft-input>');
    });

    it('reflects the display modifiers on the host', function () {
        $html = Input::make()
            ->id('i')
            ->size('small')
            ->small()
            ->center()
            ->monospace()
            ->hiddenInput()
            ->toHtml();

        expect($html)->toContain('size="small"')
            ->and($html)->toContain(' small')
            ->and($html)->toContain(' center')
            ->and($html)->toContain(' monospace')
            ->and($html)->toContain(' hidden-input');
    });
});

describe('suffix', function () {
    it('renders the suffix slot with a linked hidden description', function () {
        $html = Input::make()->id('i')->suffix('MB')->toHtml();

        expect($html)->toContainTag('div', ['slot' => 'suffix', 'class' => 'label light', 'aria-hidden' => 'true'])
            ->and($html)->toContain('>MB</div>')
            ->and($html)->toContainTag('span', ['id' => 'i-desc', 'hidden' => true])
            ->and($html)->toContain('aria-describedby="i-desc"');
    });

    it('appends the suffix description to a configured describedBy', function () {
        $html = Input::make()->id('i')->suffix('MB')->describedBy('help')->toHtml();

        expect($html)->toContain('aria-describedby="help i-desc"');
    });

    it('encodes the suffix text', function () {
        expect(Input::make()->id('i')->suffix('<b>MB</b>')->toHtml())
            ->toContain('&lt;b&gt;MB&lt;/b&gt;');
    });
});
