<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Cp;
use CraftCms\Cms\Support\File;
use Symfony\Component\DomCrawler\Crawler;

beforeEach(function () {
    $this->dir = storage_path('framework/testing/brand-images');
    File::ensureDirectoryExists($this->dir);
});

afterEach(function () {
    File::deleteDirectory($this->dir);
});

it('inlines an SVG path in place of the URL, keeping its styles matched to its elements', function () {
    File::put("$this->dir/icon.svg", '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><style>#bg .shape { fill: light-dark(#000, #fff); }</style><g id="bg"><rect class="shape" width="32" height="32"/></g></svg>');
    Cms::config()->cpIconPath = "$this->dir/icon.svg";
    Cms::config()->cpIconUrl = 'https://example.test/icon.png';

    $html = Cp::iconHtml();
    $svg = new Crawler($html)->filter('svg');
    $group = $svg->filter('g');
    $style = $svg->filter('style')->text();

    expect($html)->not->toContain('<?xml')
        ->and($svg->filter('img'))->toHaveCount(0)
        ->and($style)->toContain(sprintf('#%s .%s', $group->attr('id'), $group->filter('rect')->attr('class')))
        ->and($style)->toContain('light-dark(#000, #fff)');
});

it('embeds a raster path as a data URL', function () {
    File::put("$this->dir/logo.png", base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
    Cms::config()->cpLogoPath = "$this->dir/logo.png";

    $img = new Crawler(Cp::logoHtml())->filter('img');

    expect($img->attr('src'))->toStartWith('data:image/png;base64,')
        ->and($img->attr('alt'))->toBe('');
});

it('falls back to the URL when the path is not a file', function () {
    Cms::config()->cpLogoPath = "$this->dir/missing.svg";
    Cms::config()->cpLogoUrl = 'https://example.test/logo.svg';

    expect(new Crawler(Cp::logoHtml())->filter('img')->attr('src'))->toBe('https://example.test/logo.svg');
});
