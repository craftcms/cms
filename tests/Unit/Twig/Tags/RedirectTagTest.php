<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\TwigRenderer;
use Illuminate\Http\Exceptions\HttpResponseException;
use Twig\Error\RuntimeError;

beforeEach(function () {
    $this->renderer = app(TwigRenderer::class);
});

it('renders a redirect response for the default status', function () {
    $this->expectException(RuntimeError::class);
    $this->renderer->renderString('{% redirect "/foo" %}');
});

it('renders a redirect response for a custom status', function () {
    try {
        $this->renderer->renderString('{% redirect "/bar" 301 %}');
    } catch (RuntimeError $e) {
        /** @var HttpResponseException $responseException */
        $responseException = $e->getPrevious();

        expect($responseException->getResponse()->getStatusCode())->toBe(301);
        expect($responseException->getResponse()->headers->get('Location'))->toContain('/bar');
    }
});

it('sets flash notice on redirect', function () {
    try {
        $this->renderer->renderString('{% redirect "/foo" with notice "Saved!" %}');
    } catch (RuntimeError) {
    }

    expect(session()->get('notice'))->toBe('Saved!');
});

it('sets flash error on redirect', function () {
    try {
        $this->renderer->renderString('{% redirect "/foo" with error "Oops" %}');
    } catch (RuntimeError) {
    }

    expect(session()->get('error'))->toBe('Oops');
});
