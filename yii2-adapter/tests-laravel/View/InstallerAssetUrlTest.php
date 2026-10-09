<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\View\TemplateManager;

it('renders the legacy starter homepage background URL', function(): void {
    Cms::setIsInstalled(false);
    Cms::config()->buildId('homepage-build');

    $url = app(TemplateManager::class)->renderString(<<<'TWIG'
        {{ view.getAssetManager().getPublishedUrl('@app/web/assets/installer/dist', true, 'images/installer-bg.png') }}
        TWIG);

    expect(parse_url($url, PHP_URL_PATH))->toBe('/vendor/craft/legacy/installer/dist/images/installer-bg.png');

    parse_str(html_entity_decode((string) parse_url($url, PHP_URL_QUERY)), $query);

    expect($query)->toMatchArray(['v' => Cms::version(), 'buildId' => 'homepage-build']);
});
