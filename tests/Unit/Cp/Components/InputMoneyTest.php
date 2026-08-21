<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\InputMoney;
use Symfony\Component\DomCrawler\Crawler;

it('renders a localized money web component with canonical inputs', function () {
    $crawler = new Crawler(InputMoney::make()
        ->id('price')
        ->name('price')
        ->value('12,50')
        ->currency('EUR')
        ->locale('nl_BE')
        ->showCurrency(false)
        ->toHtml());

    expect($crawler->filter('craft-input-money[currency="EUR"][locale="nl_BE"][show-currency="false"]'))->toHaveCount(1)
        ->and($crawler->filter('input[slot="input"][name="price[value]"][value="12,50"]'))->toHaveCount(1)
        ->and($crawler->filter('input[type="hidden"][name="price[locale]"][value="nl_BE"]'))->toHaveCount(1);
});
