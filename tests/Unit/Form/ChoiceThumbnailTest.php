<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Enums\ChoicePresentation;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use Symfony\Component\DomCrawler\Crawler;

function thumbnailChoiceHtml(array $options): Crawler
{
    $context = new FormContext;
    $payload = app(FormResolver::class)->resolve(Form::make([
        Field::make('View Mode', Choice::make('viewMode')
            ->presentation(ChoicePresentation::Radios)
            ->options($options)
            ->value('list')),
    ]), $context);

    return new Crawler(app(FormHtmlRenderer::class)->render($payload));
}

it('renders an option thumbnail above its radio, bound to the same input', function () {
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
    $thumbnails = $crawler->filter('label.radio-thumbnail');

    expect($thumbnails)->toHaveCount(2)
        ->and($thumbnails->first()->filter('img')->attr('src'))->toBe('/build/images/view-modes/list.svg')
        ->and($thumbnails->first()->filter('img')->attr('width'))->toBe('48')
        ->and($thumbnails->eq(1)->filter('img')->attr('width'))->toBe('80')
        // Empty alt: the label text beside the radio already names the choice.
        ->and($thumbnails->first()->filter('img')->attr('alt'))->toBe('');

    // Clicking the illustration selects its radio, as it did in Craft 5.
    $for = $thumbnails->first()->attr('for');
    expect($for)->not->toBeNull()
        ->and($crawler->filter("input#$for")->attr('value'))->toBe('list');
});

it('renders an aspect ratio only when the thumbnail asks for one', function () {
    $crawler = thumbnailChoiceHtml([
        ['label' => 'List', 'value' => 'list', 'thumbnail' => [
            'src' => '/build/list.svg',
            'aspectRatio' => '4 / 5',
        ]],
        ['label' => 'Cards', 'value' => 'cards', 'thumbnail' => ['src' => '/build/cards.svg']],
    ]);
    $images = $crawler->filter('label.radio-thumbnail img');

    expect($images->first()->attr('style'))->toContain('aspect-ratio: 4 / 5')
        ->and($images->eq(1)->attr('style'))->toBeNull()
        // A thumbnail needs nothing but a src.
        ->and($images->eq(1)->attr('src'))->toBe('/build/cards.svg')
        ->and($images->eq(1)->attr('width'))->toBeNull();
});

it('marks the group so the options lay out as a row', function () {
    $withThumbs = thumbnailChoiceHtml([
        ['label' => 'List', 'value' => 'list', 'thumbnail' => ['src' => '/build/list.svg']],
    ]);
    $without = thumbnailChoiceHtml([
        ['label' => 'List', 'value' => 'list'],
    ]);

    expect($withThumbs->filter('craft-radio-group')->attr('thumbnails'))->not->toBeNull()
        ->and($without->filter('craft-radio-group')->attr('thumbnails'))->toBeNull()
        ->and($without->filter('label.radio-thumbnail'))->toHaveCount(0);
});
