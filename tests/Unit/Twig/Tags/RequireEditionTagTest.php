<?php

declare(strict_types=1);

use CraftCms\Cms\Edition;
use CraftCms\Cms\Twig\TemplateRenderer;

beforeEach(function () {
    $this->renderer = app(TemplateRenderer::class);
});

it('renders normally when the edition meets the requirement', function () {
    Edition::set(Edition::Pro);

    $result = $this->renderer->renderString('{% requireEdition "pro" %}Allowed');

    expect(trim((string) $result))->toBe('Allowed');
});

it('renders normally when a higher edition than required is installed', function () {
    Edition::set(Edition::Pro);

    $result = $this->renderer->renderString('{% requireEdition "team" %}Allowed');

    expect(trim((string) $result))->toBe('Allowed');
});
