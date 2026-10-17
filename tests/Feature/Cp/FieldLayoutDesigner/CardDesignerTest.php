<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cp\FieldLayoutDesigner\CardDesigner;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\FieldLayout;
use Symfony\Component\DomCrawler\Crawler;

function cardPreview(string $elementType): Crawler
{
    $html = app(CardDesigner::class)->previewHtml(new FieldLayout(['type' => $elementType]));

    return new Crawler("<div>$html</div>");
}

it('shows a sample chip above the card', function () {
    $preview = cardPreview(Entry::class);
    $chip = $preview->filter('craft-chip.cvd-chip');

    expect($chip->count())->toBe(1)
        ->and($chip->text())->toContain('The Quick Brown Fox')
        ->and($preview->filter('craft-card .label')->text())->toContain('The Quick Brown Fox')
        ->and($preview->filter('div > *')->first()->nodeName())->toBe('craft-chip');
});

it('shows the status in the chip and card for element types with statuses', function (string $elementType, bool $hasStatuses) {
    $preview = cardPreview($elementType);
    $chip = $preview->filter('craft-chip.cvd-chip');

    expect($chip->filter('[slot="status"]')->count())->toBe((int) $hasStatuses)
        ->and($chip->attr('show-status'))->toBe($hasStatuses ? '' : null)
        ->and($preview->filter('craft-card ul')->count())->toBe((int) $hasStatuses);
})->with([
    'entries' => [Entry::class, true],
    'addresses' => [Address::class, false],
]);

it('shows a thumbnail in the chip when the cards have one', function (string $elementType, bool $hasThumb) {
    $chip = cardPreview($elementType)->filter('craft-chip.cvd-chip');

    expect($chip->filter('[slot="thumbnail"]')->count())->toBe((int) $hasThumb)
        ->and($chip->attr('show-thumb'))->toBe($hasThumb ? '' : null);
})->with([
    'entries without a thumbnail field' => [Entry::class, false],
    'assets' => [Asset::class, true],
]);

it('renders the thumbnail alignment setting as radios shown as buttons', function () {
    $layout = new FieldLayout(['type' => Asset::class]);
    $layout->setCardThumbAlignment('start');
    $designer = new Crawler('<div>'.app(CardDesigner::class)->html($layout).'</div>');

    // Radios, rather than toggle buttons, so the whole setting is one tab stop
    // whose selection the arrow keys move.
    $group = $designer->filter('craft-radio-group#thumb-alignment[buttons]');
    $radios = $group->filter('input[type="radio"][name="thumb-alignment"]');

    expect($group->count())->toBe(1)
        ->and($radios->each(fn (Crawler $radio) => $radio->attr('value')))->toBe(['start', 'end'])
        ->and($group->filter('input[value="start"]')->attr('checked'))->not->toBeNull()
        ->and($group->filter('input[value="end"]')->attr('checked'))->toBeNull()
        ->and($designer->filter('craft-tooltip[for="thumb-alignment-start"]')->text())->toBe('Left')
        ->and($designer->filter('craft-tooltip[for="thumb-alignment-end"]')->text())->toBe('Right')
        ->and($designer->filter('craft-button-group#thumb-alignment')->count())->toBe(0);
});
