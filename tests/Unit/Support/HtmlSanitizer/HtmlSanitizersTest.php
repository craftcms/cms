<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\HtmlSanitizers as HtmlSanitizersFacade;
use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers;
use Illuminate\Support\Collection;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

beforeEach(function () {
    $this->sanitizers = app(HtmlSanitizers::class);
});

test('default sanitizer removes unknown attributes and keeps craft additions', function () {
    $sanitized = $this->sanitizers->sanitize('<div data-oembed-url="https://www.youtube.com/watch?v=test" bad="x"></div><oembed url="https://www.youtube.com/watch?v=test"></oembed><a download href="/foo" rel="external custom">Link</a>');

    expect($sanitized)->toMatchSnapshot();
});

test('registered sanitizer names can be used', function () {
    $this->sanitizers->register('links-only', new HtmlSanitizer((new HtmlSanitizerConfig)
        ->allowElement('a')
        ->allowAttribute('href', ['a'])
    ));

    $sanitized = $this->sanitizers->sanitize('<a href="https://craftcms.com" onclick="bad()">Craft</a><strong>bad</strong>', 'links-only');

    expect($sanitized)->toMatchSnapshot();
});

test('all returns registered sanitizers', function () {
    $linksOnlySanitizer = new HtmlSanitizer((new HtmlSanitizerConfig)
        ->allowElement('a')
        ->allowAttribute('href', ['a'])
    );

    $this->sanitizers->register('links-only', $linksOnlySanitizer);

    $sanitizers = $this->sanitizers->all();

    expect($sanitizers)
        ->toBeInstanceOf(Collection::class)
        ->toHaveKeys(['default', 'links-only'])
        ->and($sanitizers->get('default'))->toBeInstanceOf(HtmlSanitizerInterface::class)
        ->and($sanitizers->get('links-only'))->toBe($linksOnlySanitizer);
});

test('default config can be customized with a callback', function () {
    $this->sanitizers->defaults(fn (HtmlSanitizerConfig $config) => $config->allowAttribute('class', ['p']));

    $sanitized = $this->sanitizers->sanitize('<p class="lead" onclick="bad()">Hello</p>');

    expect($sanitized)->toMatchSnapshot();
});

test('default config can be reused for custom sanitizers', function () {
    $config = $this->sanitizers->defaultConfig()
        ->allowElement('iframe')
        ->allowAttribute('src', ['iframe']);

    $this->sanitizers->register('custom', new HtmlSanitizer($config));

    $sanitized = $this->sanitizers->sanitize('<iframe src="https://example.com"></iframe>', 'custom');

    expect($sanitized)->toMatchSnapshot();
});

test('sanitizer instances can be used directly', function () {
    $sanitizer = new HtmlSanitizer((new HtmlSanitizerConfig)
        ->allowElement('p')
        ->allowAttribute('class', ['p']));

    $sanitized = $this->sanitizers->sanitize('<p class="lead">Hello</p>', $sanitizer);

    expect($sanitized)->toMatchSnapshot();
});

test('facade resolves the sanitizer service', function () {
    expect(HtmlSanitizersFacade::sanitizer())->toBeInstanceOf(HtmlSanitizerInterface::class);
    expect(HtmlSanitizersFacade::defaultConfig())->toBeInstanceOf(HtmlSanitizerConfig::class);
});
