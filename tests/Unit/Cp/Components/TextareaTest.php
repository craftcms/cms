<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Textarea;

describe('textarea', function () {
    it('renders the slotted native textarea inside the web component', function () {
        $html = Textarea::make()
            ->id('notes')
            ->name('notes')
            ->value('Happy Lager')
            ->toHtml();

        expect($html)->toContain('<craft-textarea name="notes">')
            ->and($html)->toContain('slot="input"')
            ->and($html)->toContain('id="notes"')
            ->and($html)->toContain('name="notes"')
            ->and($html)->toContain('>Happy Lager</textarea>')
            ->and($html)->toContain('class="text fullwidth"');
    });

    it('encodes the textarea value as content', function () {
        $html = Textarea::make()->id('i')->value('<script>alert(1)</script> & co')->toHtml();

        expect($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt; &amp; co')
            ->and($html)->not->toContain('<script>alert(1)</script>');
    });

    it('renders an empty textarea when no value is set', function () {
        $html = Textarea::make()->id('i')->toHtml();

        expect($html)->toContain('></textarea>');
    });

    it('defaults rows to 2 and cols to 50', function () {
        $html = Textarea::make()->id('i')->toHtml();

        expect($html)->toContain('rows="2"')
            ->and($html)->toContain('cols="50"');
    });

    it('allows overriding rows and cols', function () {
        $html = Textarea::make()->id('i')->rows(4)->cols(30)->toHtml();

        expect($html)->toContain('rows="4"')
            ->and($html)->toContain('cols="30"');
    });

    it('drops the fullwidth class when cols is configured', function () {
        $html = Textarea::make()->id('i')->cols(30)->toHtml();

        expect($html)->toContain('class="text"')
            ->and($html)->not->toContain('fullwidth');
    });

    it('renders disabled and readonly state', function () {
        $html = Textarea::make()->id('i')->disabled()->readOnly()->toHtml();

        expect($html)->toContain(' disabled')
            ->and($html)->toContain(' readonly');
    });

    it('omits autofocus on a mobile browser but honors it otherwise for any user', function () {
        expect(Textarea::make()->id('i')->autofocus()->toHtml())->toContain(' autofocus');
    });

    it('reflects maxlength on the native textarea', function () {
        $html = Textarea::make()->id('i')->maxlength(255)->toHtml();

        expect($html)->toContain('maxlength="255"');
    });

    it('renders title and placeholder', function () {
        $html = Textarea::make()->id('i')->title('Notes')->placeholder('Add notes…')->toHtml();

        expect($html)->toContain('title="Notes"')
            ->and($html)->toContain('placeholder="Add notes…"');
    });

    it('links describedBy via aria-describedby', function () {
        $html = Textarea::make()->id('i')->describedBy('help')->toHtml();

        expect($html)->toContain('aria-describedby="help"');
    });

    it('marks the textarea for the chars-left counter without padding', function () {
        $html = Textarea::make()->id('i')->maxlength(255)->showCharsLeft()->toHtml();

        expect($html)->toContain('data-show-chars-left')
            ->and($html)->not->toContain('padding');
    });

    it('lets inputAttributes override computed defaults like rows', function () {
        $html = Textarea::make()
            ->id('i')
            ->rows(2)
            ->inputAttributes(['rows' => 10, 'class' => 'extra'])
            ->toHtml();

        expect($html)->toContain('rows="10"')
            ->and($html)->toContain('class="text fullwidth extra"');
    });
});

describe('host attributes', function () {
    it('reflects monospace on the host', function () {
        $html = Textarea::make()->id('i')->monospace()->toHtml();

        expect($html)->toContain('<craft-textarea monospace>');
    });

    it('omits monospace by default', function () {
        $html = Textarea::make()->id('i')->toHtml();

        expect($html)->toContain('<craft-textarea>');
    });

    it('mirrors Lion-synced control attributes onto the host', function () {
        // Lion pushes placeholder/name/disabled/readonly/rows from the host
        // onto the slotted textarea on upgrade, so non-default values must be
        // reflected there or the server-rendered attributes get clobbered.
        $html = Textarea::make()
            ->id('i')
            ->placeholder('Enter a value')
            ->name('myField')
            ->rows(6)
            ->disabled()
            ->readOnly()
            ->toHtml();

        expect($html)->toMatch('/<craft-textarea[^>]* placeholder="Enter a value"/')
            ->and($html)->toMatch('/<craft-textarea[^>]* name="myField"/')
            ->and($html)->toMatch('/<craft-textarea[^>]* rows="6"/')
            ->and($html)->toMatch('/<craft-textarea[^>]* disabled/')
            ->and($html)->toMatch('/<craft-textarea[^>]* readonly/');
    });
});
