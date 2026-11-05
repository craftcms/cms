<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\ElementSelect;
use CraftCms\Cms\User\Elements\User;
use Symfony\Component\DomCrawler\Crawler;

it('renders selected elements as chips and hides the button at the limit', function () {
    $user = User::findOne();

    $crawler = new Crawler(ElementSelect::make()
        ->name('authorIds')
        ->elementType(User::class)
        ->single()
        ->elements([$user])
        ->toHtml());

    expect($crawler->filter(".elements [data-id=\"$user->id\"]"))->toHaveCount(1)
        ->and($crawler->filter("input[type=\"hidden\"][name=\"authorIds\"][value=\"$user->id\"]"))->toHaveCount(1)
        ->and($crawler->filter('craft-button.add.hidden'))->toHaveCount(1);
});
