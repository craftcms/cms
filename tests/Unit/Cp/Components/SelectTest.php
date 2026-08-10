<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Select;
use Symfony\Component\DomCrawler\Crawler;

it('renders a selected native option in the web component', function () {
    $crawler = new Crawler(Select::make()
        ->id('language')
        ->name('language')
        ->value('en')
        ->options([
            ['label' => 'French', 'value' => 'fr'],
            ['label' => 'English', 'value' => 'en'],
        ])
        ->required()
        ->toHtml());

    expect($crawler->filter('craft-select[name="language"][required]'))->toHaveCount(1)
        ->and($crawler->filter('select#language[name="language"][required][slot="input"]'))->toHaveCount(1)
        ->and($crawler->filter('option[value="en"][selected]')->text())->toBe('English');
});

it('renders multiple values and disabled options', function () {
    $crawler = new Crawler(Select::make()
        ->id('topics')
        ->name('topics[]')
        ->value(['one', 'two'])
        ->multiple()
        ->options([
            ['label' => 'One', 'value' => 'one'],
            ['label' => 'Two', 'value' => 'two', 'disabled' => true],
        ])
        ->toHtml());

    expect($crawler->filter('craft-select select[multiple][name="topics[]"]'))->toHaveCount(1)
        ->and($crawler->filter('option[selected]'))->toHaveCount(2)
        ->and($crawler->filter('option[value="two"][disabled]'))->toHaveCount(1);
});
