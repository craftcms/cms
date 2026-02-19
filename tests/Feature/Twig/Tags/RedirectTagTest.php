<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\TemplateRenderer;
use Twig\Error\RuntimeError;

beforeEach(function () {
    $this->renderer = app(TemplateRenderer::class);
});

it('redirects with default 302 status', function () {
    try {
        $this->renderer->renderString('{% redirect "/foo" %}');
        $this->fail('Expected RuntimeError to be thrown');
    } catch (RuntimeError) {
        $response = Craft::$app->getResponse();

        expect($response->getStatusCode())->toBe(302);
        expect($response->getHeaders()->get('Location'))->toContain('/foo');
    }
});

it('redirects with custom 301 status', function () {
    try {
        $this->renderer->renderString('{% redirect "/bar" 301 %}');
        $this->fail('Expected RuntimeError to be thrown');
    } catch (RuntimeError) {
        $response = Craft::$app->getResponse();

        expect($response->getStatusCode())->toBe(301);
        expect($response->getHeaders()->get('Location'))->toContain('/bar');
    }
});

it('sets flash notice on redirect', function () {
    try {
        $this->renderer->renderString('{% redirect "/foo" with notice "Saved!" %}');
        $this->fail('Expected RuntimeError to be thrown');
    } catch (RuntimeError) {
        expect(Craft::$app->getSession()->getNotice())->toBe('Saved!');
    }
});

it('sets flash error on redirect', function () {
    try {
        $this->renderer->renderString('{% redirect "/foo" with error "Oops" %}');
        $this->fail('Expected RuntimeError to be thrown');
    } catch (RuntimeError) {
        expect(Craft::$app->getSession()->getError())->toBe('Oops');
    }
});
