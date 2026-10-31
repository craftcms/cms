<?php

declare(strict_types=1);

use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Enums\ChoicePresentation;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

function thumbnailChoiceHtml(array $options): Crawler
{
    $context = new UiContext;
    $payload = app(UiResolver::class)->resolve(Ui::make([
        Field::make('View Mode', Choice::make('viewMode')
            ->presentation(ChoicePresentation::Radios)
            ->options($options)
            ->value('list')),
    ]), $context);

    return new Crawler(app(UiHtmlRenderer::class)->render($payload));
}

it('renders an option thumbnail into its radio\'s thumbnail slot', function () {
    $crawler = thumbnailChoiceHtml([
        ['label' => 'List', 'value' => 'list', 'thumbnail' => [
            'src' => '/build/images/view-modes/list.svg',
            'width' => 48,
            'height' => 60,
            'aspectRatio' => '48 / 60',
        ]],
        ['label' => 'Cards', 'value' => 'cards', 'thumbnail' => [
            'src' => '/build/images/view-modes/cards.svg',
            'width' => 80,
            'height' => 60,
        ]],
    ]);
    $radios = $crawler->filter('craft-radio');
    $first = $radios->first()->filter('img[slot="thumbnail"]');

    expect($crawler->filter('img[slot="thumbnail"]'))->toHaveCount(2)
        ->and($first->attr('src'))->toBe('/build/images/view-modes/list.svg')
        ->and($first->attr('width'))->toBe('48')
        ->and($radios->eq(1)->filter('img[slot="thumbnail"]')->attr('width'))->toBe('80')
        ->and($first->attr('alt'))->toBe('')
        ->and($radios->first()->filter('input[slot="input"]')->attr('value'))->toBe('list');
});

it('renders an aspect ratio only when the thumbnail asks for one', function () {
    $crawler = thumbnailChoiceHtml([
        ['label' => 'List', 'value' => 'list', 'thumbnail' => [
            'src' => '/build/list.svg',
            'aspectRatio' => '4 / 5',
        ]],
        ['label' => 'Cards', 'value' => 'cards', 'thumbnail' => ['src' => '/build/cards.svg']],
    ]);
    $images = $crawler->filter('img[slot="thumbnail"]');

    expect($images->first()->attr('style'))->toContain('aspect-ratio: 4 / 5')
        ->and($images->eq(1)->attr('style'))->toBeNull()
        // A thumbnail needs nothing but a src.
        ->and($images->eq(1)->attr('src'))->toBe('/build/cards.svg')
        ->and($images->eq(1)->attr('width'))->toBeNull();
});

it('marks the group so the options show as tiles', function () {
    $withThumbs = thumbnailChoiceHtml([
        ['label' => 'List', 'value' => 'list', 'thumbnail' => ['src' => '/build/list.svg']],
    ]);
    $without = thumbnailChoiceHtml([
        ['label' => 'List', 'value' => 'list'],
    ]);

    expect($withThumbs->filter('craft-radio-group')->attr('thumbnails'))->not->toBeNull()
        ->and($without->filter('craft-radio-group')->attr('thumbnails'))->toBeNull()
        ->and($without->filter('img[slot="thumbnail"]'))->toHaveCount(0);
});

it('inlines an SVG thumbnail, hidden from assistive tech', function () {
    $crawler = thumbnailChoiceHtml([
        ['label' => 'Cards', 'value' => 'cards', 'thumbnail' => [
            'svg' => '<svg viewBox="0 0 80 60"><rect stroke="#828FA4" width="79" height="59"/></svg>',
            'aspectRatio' => '80 / 60',
        ]],
    ]);
    $thumbnail = $crawler->filter('craft-radio > [slot="thumbnail"]');

    expect($thumbnail)->toHaveCount(1)
        ->and($thumbnail->nodeName())->toBe('span')
        ->and($thumbnail->attr('aria-hidden'))->toBe('true')
        ->and($thumbnail->attr('style'))->toContain('aspect-ratio: 80 / 60')
        ->and($thumbnail->filter('svg > rect')->attr('stroke'))->toBe('#828FA4')
        ->and($crawler->filter('img[slot="thumbnail"]'))->toHaveCount(0);
});
