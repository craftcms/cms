<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\TemplateRenderer;

beforeEach(function () {
    $this->renderer = app(TemplateRenderer::class);
});

it('sets a response header', function () {
    $this->renderer->renderString('{% header "X-Custom: test-value" %}');

    expect(Craft::$app->getResponse()->getHeaders()->get('X-Custom'))->toBe('test-value');
});

it('sets a header with colons in the value', function () {
    $this->renderer->renderString('{% header "X-URL: https://example.com" %}');

    expect(Craft::$app->getResponse()->getHeaders()->get('X-URL'))->toBe('https://example.com');
});

it('sets multiple headers', function () {
    $this->renderer->renderString('{% header "X-First: one" %}{% header "X-Second: two" %}');

    expect(Craft::$app->getResponse()->getHeaders()->get('X-First'))->toBe('one');
    expect(Craft::$app->getResponse()->getHeaders()->get('X-Second'))->toBe('two');
});

it('supports dynamic header values', function () {
    $this->renderer->renderString(
        '{% header "X-Dynamic: " ~ value %}',
        ['value' => 'dynamic-value'],
    );

    expect(Craft::$app->getResponse()->getHeaders()->get('X-Dynamic'))->toBe('dynamic-value');
});
