<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Middleware\SetHeaders;
use CraftCms\Cms\View\TemplateManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

function renderAndApplyHeaderHeaders(string $template, array $variables = []): Response
{
    app(TemplateManager::class)->renderString($template, $variables);

    return app(SetHeaders::class)->handle(Request::create('foo'), fn () => new Response);
}

it('sets a response header', function () {
    $response = renderAndApplyHeaderHeaders('{% header "X-Custom: test-value" %}');

    expect($response->headers->get('X-Custom'))->toBe('test-value');
});

it('sets a header with colons in the value', function () {
    $response = renderAndApplyHeaderHeaders('{% header "X-URL: https://example.com" %}');

    expect($response->headers->get('X-URL'))->toBe('https://example.com');
});

it('sets multiple headers', function () {
    $response = renderAndApplyHeaderHeaders('{% header "X-First: one" %}{% header "X-Second: two" %}');

    expect($response->headers->get('X-First'))->toBe('one');
    expect($response->headers->get('X-Second'))->toBe('two');
});

it('supports dynamic header values', function () {
    $response = renderAndApplyHeaderHeaders(
        '{% header "X-Dynamic: " ~ value %}',
        ['value' => 'dynamic-value'],
    );

    expect($response->headers->get('X-Dynamic'))->toBe('dynamic-value');
});
