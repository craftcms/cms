<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\TemplateRenderer;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Error\RuntimeError;

beforeEach(function () {
    $this->renderer = app(TemplateRenderer::class);
});

it('throws a NotFoundHttpException for 404', function () {
    try {
        $this->renderer->renderString('{% exit 404 %}');
        $this->fail('Expected exception was not thrown');
    } catch (RuntimeError $e) {
        expect($e->getPrevious())->toBeInstanceOf(NotFoundHttpException::class);
    }
});

it('throws an HttpException with the correct status code', function (int $statusCode) {
    try {
        $this->renderer->renderString("{% exit $statusCode %}");
        $this->fail('Expected exception was not thrown');
    } catch (RuntimeError $e) {
        expect($e->getPrevious())->toBeInstanceOf(HttpException::class);
        expect($e->getPrevious()->getStatusCode())->toBe($statusCode);
    }
})->with([
    '403 Forbidden' => [403],
    '500 Server Error' => [500],
    '503 Service Unavailable' => [503],
    '418 Teapot' => [418],
]);

it('includes the message in the exception', function () {
    try {
        $this->renderer->renderString("{% exit 404 'Page not found' %}");
        $this->fail('Expected exception was not thrown');
    } catch (RuntimeError $e) {
        expect($e->getPrevious())->toBeInstanceOf(NotFoundHttpException::class);
        expect($e->getPrevious()->getMessage())->toBe('Page not found');
    }
});

it('includes the message for non-404 status codes', function () {
    try {
        $this->renderer->renderString("{% exit 500 'Server error' %}");
        $this->fail('Expected exception was not thrown');
    } catch (RuntimeError $e) {
        expect($e->getPrevious())->toBeInstanceOf(HttpException::class);
        expect($e->getPrevious()->getMessage())->toBe('Server error');
    }
});
