<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\TemplateRenderer;

beforeEach(function () {
    $this->renderer = app(TemplateRenderer::class);
});

it('renders body content', function () {
    $result = $this->renderer->renderString('{% cache %}Cached content{% endcache %}');

    expect(trim((string) $result))->toBe('Cached content');
});

it('caches content on second render with global cache', function () {
    $result1 = $this->renderer->renderString('{% cache globally using key "test-key" %}Hello{% endcache %}');
    $result2 = $this->renderer->renderString('{% cache globally using key "test-key" %}Goodbye{% endcache %}');

    expect(trim((string) $result1))->toBe('Hello');
    expect(trim((string) $result2))->toBe('Hello');
});

it('renders dynamic content when cache is bypassed via unless', function () {
    $result1 = $this->renderer->renderString('{% cache using key "unless-key" unless true %}First{% endcache %}');
    $result2 = $this->renderer->renderString('{% cache using key "unless-key" unless true %}Second{% endcache %}');

    expect(trim((string) $result1))->toBe('First');
    expect(trim((string) $result2))->toBe('Second');
});

it('renders with explicit key', function () {
    $result = $this->renderer->renderString('{% cache using key "explicit-key" %}Keyed content{% endcache %}');

    expect(trim((string) $result))->toBe('Keyed content');
});

it('renders globally cached content', function () {
    $result = $this->renderer->renderString('{% cache globally %}Global content{% endcache %}');

    expect(trim((string) $result))->toBe('Global content');
});
