<?php

declare(strict_types=1);

use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateRoots;

it('keeps ordered template roots separate by mode', function () {
    $registry = app(TemplateRoots::class);

    $registry->register(TemplateMode::Cp, 'plugin', '/first', '/second', '/first');
    $registry->register(TemplateMode::Site, 'plugin', '/site');

    expect($registry->roots(TemplateMode::Cp))->toBe(['plugin' => ['/first', '/second']])
        ->and($registry->roots(TemplateMode::Site))->toBe(['plugin' => ['/site']]);
});

it('removes namespaces within one template mode', function () {
    $registry = app(TemplateRoots::class);
    $registry->register(TemplateMode::Cp, 'plugin', '/first');
    $registry->register(TemplateMode::Cp, 'removed', '/removed');
    $registry->register(TemplateMode::Site, 'plugin', '/site');

    $registry->remove(TemplateMode::Cp, 'removed', 'missing');

    expect($registry->roots(TemplateMode::Cp))->toBe(['plugin' => ['/first']])
        ->and($registry->roots(TemplateMode::Site))->toBe(['plugin' => ['/site']]);
});

it('exposes current roots through template modes', function () {
    $registry = app(TemplateRoots::class);

    expect(TemplateMode::Cp->templateRoots())->not()->toHaveKey('plugin');

    $registry->register(TemplateMode::Cp, 'plugin', '/templates');

    expect(TemplateMode::Cp->templateRoots())->toBe(['plugin' => ['/templates']]);

    $registry->remove(TemplateMode::Cp, 'plugin');

    expect(TemplateMode::Cp->templateRoots())->not()->toHaveKey('plugin');
});
