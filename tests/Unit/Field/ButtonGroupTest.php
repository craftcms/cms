<?php

declare(strict_types=1);

use CraftCms\Cms\Field\ButtonGroup;
use CraftCms\Cms\Field\FieldContext;
use Symfony\Component\DomCrawler\Crawler;

function buttonGroupInput(bool $iconsOnly): Crawler
{
    $field = new ButtonGroup([
        'handle' => 'align',
        'iconsOnly' => $iconsOnly,
        'options' => [
            ['label' => 'Left', 'value' => 'left', 'icon' => 'arrow-left', 'default' => false],
            ['label' => 'Right', 'value' => 'right', 'default' => false],
        ],
    ]);

    return new Crawler($field->getInlineInputHtml($field->normalizeValue('left', null), null));
}

it('renders its options as radios drawn as buttons', function () {
    $crawler = buttonGroupInput(false);
    $inputs = $crawler->filter('craft-radio-group[buttons] craft-radio > input[type="radio"]');

    expect($crawler->filter('craft-button-group'))->toHaveCount(0)
        ->and($inputs)->toHaveCount(2)
        ->and($inputs->each(fn (Crawler $input) => $input->attr('name')))->toBe(['align', 'align'])
        ->and($inputs->eq(0)->attr('checked'))->not->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->toBeNull()
        ->and(trim($crawler->filter('label[for="align-0"]')->text()))->toBe('Left')
        ->and(trim($crawler->filter('label[for="align-1"]')->text()))->toBe('Right');
});

it('names an icon-only option by its icon, and shows the label on hover', function () {
    $crawler = buttonGroupInput(true);
    $icon = $crawler->filter('label[for="align-0"] craft-icon');

    expect(trim($crawler->filter('label[for="align-0"]')->text()))->toBe('')
        ->and($icon->attr('name'))->toBe('arrow-left')
        ->and($icon->attr('label'))->toBe('Left')
        ->and($crawler->filter('craft-radio')->eq(0)->attr('title'))->toBe('Left')
        ->and(trim($crawler->filter('label[for="align-1"]')->text()))->toBe('Right')
        ->and($crawler->filter('craft-radio')->eq(1)->attr('title'))->toBeNull();
});

it('tells its control whether options show only their icons', function (bool $iconsOnly) {
    $field = new ButtonGroup([
        'iconsOnly' => $iconsOnly,
        'options' => [
            ['label' => 'Left', 'value' => 'left', 'icon' => 'arrow-left', 'default' => false],
        ],
    ]);

    $props = $field->uiControl(new FieldContext('value'))->props();

    expect($props['options'][0]['icon'])->toBe('arrow-left')
        ->and($props['iconsOnly'] ?? true)->toBe($iconsOnly);
})->with([
    'icons only' => true,
    'icons with labels' => false,
]);
