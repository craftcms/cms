<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\SelectRich;
use CraftCms\Cms\Shared\Enums\Color;
use Illuminate\Support\HtmlString;
use Symfony\Component\DomCrawler\Crawler;

it('renders options with their values and checks the selected one', function () {
    $crawler = new Crawler(SelectRich::make()
        ->id('entryType')
        ->name('typeId')
        ->value(2)
        ->options([
            ['label' => 'Article', 'value' => 1],
            ['label' => 'News & Updates', 'value' => 2, 'hint' => 'news'],
            ['label' => 'Event', 'value' => 3, 'disabled' => true],
        ])
        ->toHtml());

    expect($crawler->filter('craft-select-rich#entryType[name="typeId"]'))->toHaveCount(1)
        ->and($crawler->filter('craft-option'))->toHaveCount(3)
        ->and($crawler->filter('craft-option[checked]')->attr('choice-value'))->toBe('2')
        ->and($crawler->filter('craft-option[checked]')->attr('hint'))->toBe('news')
        ->and($crawler->filter('craft-option[checked]')->text())->toBe('News & Updates')
        ->and($crawler->filter('craft-option[choice-value="3"][disabled]'))->toHaveCount(1);
});

it('renders option icons and trusted label markup', function () {
    $crawler = new Crawler(SelectRich::make()
        ->options([
            ['label' => new HtmlString('<b>Bold</b>'), 'value' => 'a', 'icon' => 'newspaper', 'iconColor' => Color::Red],
            ['label' => '<b>Plain</b>', 'value' => 'b'],
        ])
        ->toHtml());

    expect($crawler->filter('craft-option[choice-value="a"] craft-icon[name="newspaper"][data-color="red"]'))->toHaveCount(1)
        ->and($crawler->filter('craft-option[choice-value="a"] b')->text())->toBe('Bold')
        ->and($crawler->filter('craft-option[choice-value="b"] b'))->toHaveCount(0)
        ->and($crawler->filter('craft-option[checked]'))->toHaveCount(0);
});

it('renders a disabled select', function () {
    expect(SelectRich::make()->name('x')->disabled()->toHtml())
        ->toContainTag('craft-select-rich', ['name' => 'x', 'disabled' => true]);
});
