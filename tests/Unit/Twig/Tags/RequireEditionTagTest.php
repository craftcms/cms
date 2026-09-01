<?php

declare(strict_types=1);

use CraftCms\Cms\Edition;
use CraftCms\Cms\View\TemplateManager;

beforeEach(function () {
    $this->manager = app(TemplateManager::class);
});

it('renders normally when the edition meets the requirement', function () {
    Edition::set(Edition::Pro);

    $result = $this->manager->renderString('{% requireEdition "pro" %}Allowed');

    expect(trim((string) $result))->toBe('Allowed');
});

it('renders normally when a higher edition than required is installed', function () {
    Edition::set(Edition::Pro);

    $result = $this->manager->renderString('{% requireEdition "team" %}Allowed');

    expect(trim((string) $result))->toBe('Allowed');
});
