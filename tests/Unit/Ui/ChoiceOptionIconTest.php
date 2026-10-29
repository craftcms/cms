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

function iconChoiceHtml(ChoicePresentation $presentation): Crawler
{
    $payload = app(UiResolver::class)->resolve(Ui::make([
        Field::make('Options', Choice::make('options')
            ->presentation($presentation)
            ->multiple($presentation === ChoicePresentation::Checkboxes)
            ->options([
                ['label' => 'Icon', 'value' => 'icon', 'icon' => 'star', 'color' => '#ff0000'],
                ['label' => 'Color', 'value' => 'color', 'color' => '#00ff00'],
                ['label' => 'Plain', 'value' => 'plain'],
            ])),
    ]), new UiContext);

    return new Crawler(app(UiHtmlRenderer::class)->render($payload));
}

it('shows each option’s icon or color before its label', function (ChoicePresentation $presentation) {
    $crawler = iconChoiceHtml($presentation);

    expect($crawler->filter('.cp-icon'))->toHaveCount(1)
        ->and($crawler->filter('.cp-icon')->attr('style'))->toContain('--icon-color: #ff0000')
        ->and($crawler->filter('.color-preview'))->toHaveCount(1)
        ->and($crawler->filter('.color-preview')->attr('style'))->toContain('#00ff00');
})->with([
    'checkboxes' => ChoicePresentation::Checkboxes,
    'radios' => ChoicePresentation::Radios,
]);

it('shows a button’s icon beside its label unless options show only their icons', function (bool $multiple) {
    $payload = app(UiResolver::class)->resolve(Ui::make([
        Field::make('Align', Choice::make('align')
            ->presentation(ChoicePresentation::Buttons)
            ->multiple($multiple)
            ->iconsOnly(false)
            ->options([
                ['label' => 'Left', 'value' => 'left', 'icon' => 'arrow-left'],
            ])),
    ]), new UiContext);
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));
    $option = $crawler->filter($multiple ? 'craft-button' : 'craft-radio')->first();

    expect(trim($option->text()))->toBe('Left')
        ->and($option->attr('title'))->toBeNull()
        ->and($option->attr('aria-label'))->toBeNull()
        ->and($option->filter('craft-icon[label]'))->toHaveCount(0);
})->with([
    'single' => false,
    'multiple' => true,
]);
