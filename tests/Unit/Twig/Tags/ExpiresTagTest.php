<?php

declare(strict_types=1);

use craft\helpers\DateTimeHelper;
use CraftCms\Cms\Twig\TemplateRenderer;

beforeEach(function () {
    $this->renderer = app(TemplateRenderer::class);
});

it('sets cache headers with duration', function () {
    $this->renderer->renderString('{% expires in 1 day %}');

    expect(Craft::$app->getResponse()->getHeaders()->get('Cache-Control'))
        ->toContain('max-age='.DateTimeHelper::relativeTimeToSeconds(1, 'day'));
    expect(Craft::$app->getResponse()->getHeaders()->get('Pragma'))->toBe('cache');
});

it('sets no-cache headers with zero duration', function () {
    $this->renderer->renderString('{% expires %}');

    expect(Craft::$app->getResponse()->getHeaders()->get('Cache-Control'))->toContain('no-cache');
});

it('supports hours', function () {
    $this->renderer->renderString('{% expires in 2 hours %}');

    expect(Craft::$app->getResponse()->getHeaders()->get('Cache-Control'))->toContain('max-age=7200');
});

it('renders body content alongside setting headers', function () {
    $result = $this->renderer->renderString('Before{% expires in 1 day %}After');

    expect(trim((string) $result))->toBe('BeforeAfter');
});
