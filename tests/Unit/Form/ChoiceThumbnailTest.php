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
        ['label' => 'List', 'value' => 'list', 'thumbSrc' => '/build/images/view-modes/list.svg', 'thumbWidth' => 48, 'thumbHeight' => 60],
        ['label' => 'Cards', 'value' => 'cards', 'thumbSrc' => '/build/images/view-modes/cards.svg', 'thumbWidth' => 80, 'thumbHeight' => 60],
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

it('marks the group so the options lay out as a row', function () {
    $withThumbs = thumbnailChoiceHtml([
        ['label' => 'List', 'value' => 'list', 'thumbSrc' => '/build/list.svg'],
    ]);
    $without = thumbnailChoiceHtml([
        ['label' => 'List', 'value' => 'list'],
    ]);

    expect($withThumbs->filter('craft-radio-group')->attr('thumbnails'))->not->toBeNull()
        ->and($without->filter('craft-radio-group')->attr('thumbnails'))->toBeNull()
        ->and($without->filter('label.radio-thumbnail'))->toHaveCount(0);
});
