<?php

declare(strict_types=1);

use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\HtmlFragment;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use CraftCms\Cms\View\LegacyAssets\LegacyAssetInterface;

class TestPreCaptureFragmentAsset implements LegacyAssetInterface
{
    public array $depends = [];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile('/pre-capture-fragment.js', ['position' => Position::Head->value]);
    }
}

class TestCapturedFragmentAsset implements LegacyAssetInterface
{
    public array $depends = [];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->js('window.capturedFragmentAsset = true;');
    }
}

beforeEach(function () {
    app()->forgetScopedInstances();

    $this->htmlStack = app(HtmlStack::class);
});

it('serializes html fragments', function () {
    $fragment = new HtmlFragment(
        '<p>Hook</p>',
        '<style>.hook{}</style>',
        '<script>window.hook = true;</script>',
    );

    expect($fragment->isEmpty())->toBeFalse()
        ->and($fragment->toArray())->toBe([
            'html' => '<p>Hook</p>',
            'headHtml' => '<style>.hook{}</style>',
            'bodyHtml' => '<script>window.hook = true;</script>',
        ])
        ->and($fragment->jsonSerialize())->toBe($fragment->toArray())
        ->and(new HtmlFragment()->isEmpty())->toBeTrue();
});

it('captures html with registered head and body assets', function () {
    $fragment = $this->htmlStack->capture(function (): string {
        $this->htmlStack->css('.prefs-hook { color: red; }');
        $this->htmlStack->jsFile('/prefs-hook.js');
        $this->htmlStack->js('window.prefsHookReady = true');

        return '<div data-hook="prefs">Preferences hook</div>';
    });

    expect($fragment->html)->toBe('<div data-hook="prefs">Preferences hook</div>')
        ->and($fragment->headHtml)->toContain('.prefs-hook { color: red; }')
        ->and($fragment->bodyHtml)->toContain('/prefs-hook.js')
        ->and($fragment->bodyHtml)->toContain('window.prefsHookReady = true')
        ->and($this->htmlStack->headHtml())->toBe('')
        // The outer drain may carry the client-asset sync script (the
        // fragment's assets do reach the browser), but no fragment content.
        ->and($this->htmlStack->bodyHtml())->not->toContain('prefs-hook')
        ->and($this->htmlStack->bodyHtml())->not->toContain('prefsHookReady');
});

it('isolates captured assets from the outer stack', function () {
    $this->htmlStack->cssFile('/outer.css');

    $fragment = $this->htmlStack->capture(function (): string {
        $this->htmlStack->cssFile('/inner.css');

        return '';
    });

    $outerHeadHtml = $this->htmlStack->headHtml();

    expect($fragment->headHtml)->toContain('/inner.css')
        ->and($fragment->headHtml)->not->toContain('/outer.css')
        ->and($outerHeadHtml)->toContain('/outer.css')
        ->and($outerHeadHtml)->not->toContain('/inner.css');
});

it('supports nested captures', function () {
    $inner = null;

    $outer = $this->htmlStack->capture(function () use (&$inner): string {
        $this->htmlStack->cssFile('/outer-captured.css');

        $inner = $this->htmlStack->capture(function (): string {
            $this->htmlStack->cssFile('/inner-captured.css');

            return '<p>Inner</p>';
        });

        return '<p>Outer</p>';
    });

    expect($inner)->toBeInstanceOf(HtmlFragment::class)
        ->and($inner->html)->toBe('<p>Inner</p>')
        ->and($inner->headHtml)->toContain('/inner-captured.css')
        ->and($inner->headHtml)->not->toContain('/outer-captured.css')
        ->and($outer->html)->toBe('<p>Outer</p>')
        ->and($outer->headHtml)->toContain('/outer-captured.css')
        ->and($outer->headHtml)->not->toContain('/inner-captured.css');
});

it('keeps preexisting pending legacy assets outside the fragment', function () {
    app(InternalAssetRegistry::class)->register(TestPreCaptureFragmentAsset::class);

    $fragment = $this->htmlStack->capture(fn (): string => '');
    $outerHeadHtml = $this->htmlStack->headHtml();

    expect($fragment->isEmpty())->toBeTrue()
        ->and($outerHeadHtml)->toContain('/pre-capture-fragment.js');
});

it('captures pending legacy assets registered during render', function () {
    $fragment = $this->htmlStack->capture(function (): string {
        app(InternalAssetRegistry::class)->register(TestCapturedFragmentAsset::class);

        return '';
    });

    expect($fragment->bodyHtml)->toContain('window.capturedFragmentAsset = true')
        ->and($this->htmlStack->bodyHtml())->not->toContain('capturedFragmentAsset');
});
