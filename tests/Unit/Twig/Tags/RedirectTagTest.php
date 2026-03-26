<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\TemplateRenderer;

beforeEach(function () {
    $this->renderer = app(TemplateRenderer::class);
});

it('renders a redirect response for the default status', function () {
    $result = $this->renderer->renderString('{% redirect "/foo" %}');

    expect($result)->toContain('Redirecting to');
    expect($result)->toContain('/foo');
});

it('renders a redirect response for a custom status', function () {
    $result = $this->renderer->renderString('{% redirect "/bar" 301 %}');

    expect($result)->toContain('Redirecting to');
    expect($result)->toContain('/bar');
});

it('sets flash notice on redirect', function () {
    $this->renderer->renderString('{% redirect "/foo" with notice "Saved!" %}');

    expect(Craft::$app->getSession()->getNotice())->toBe('Saved!');
});

it('sets flash error on redirect', function () {
    $this->renderer->renderString('{% redirect "/foo" with error "Oops" %}');

    expect(Craft::$app->getSession()->getError())->toBe('Oops');
});
