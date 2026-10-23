<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Checkbox;
use CraftCms\Cms\Cp\Components\CheckboxSelect;
use CraftCms\Cms\Cp\Enums\CheckboxSelectPresentation;
use CraftCms\Cms\Ui\Enums\AllOptionMode;
use Symfony\Component\DomCrawler\Crawler;

describe('checkbox select', function () {
    it('renders the element with wrapped items and an empty-value input', function () {
        $crawler = new Crawler(CheckboxSelect::make()
            ->id('sources')
            ->name('sources')
            ->options([
                Checkbox::make()->id('a')->name('sources[]')->value('a'),
            ])
            ->toHtml());
        $host = $crawler->filter('craft-checkbox-select');

        expect($host->attr('id'))->toBe('sources')
            ->and($host->attr('class'))->toBe('cp-checkbox-select')
            ->and($host->attr('name'))->toBe('sources')
            ->and($crawler->filter('craft-checkbox-select > input[type="hidden"][data-empty]')->attr('name'))->toBe('sources')
            ->and($crawler->filter('craft-checkbox-select > div.cp-checkbox-select__item input')->attr('value'))->toBe('a');
    });

    it('skips the empty-value input for array names', function () {
        expect(CheckboxSelect::make()->id('s')->name('sources[]')->toHtml())
            ->not->toContain('type="hidden"')
            ->toContain('name="sources"');
    });

    it('renders a checked “All” option, posting “*” under the field’s name', function () {
        $crawler = new Crawler(CheckboxSelect::make()
            ->id('sources')
            ->name('sources')
            ->allOption('All sources', checked: true)
            ->toHtml());
        $all = $crawler->filter('.cp-checkbox-select__item.all input[type="checkbox"]');

        expect($crawler->filter('craft-checkbox-select')->attr('all-label'))->toBe('All sources')
            ->and($all->attr('value'))->toBe('*')
            ->and($all->attr('name'))->toBe('sources[]')
            ->and($all->attr('checked'))->not->toBeNull();
    });

    it('lets the “All” option keep a scalar name, and carry none in each-value mode', function () {
        $scalar = new Crawler(CheckboxSelect::make()->id('s')->name('sources')
            ->allOption('All', name: 'sources')->toHtml());
        $eachValue = new Crawler(CheckboxSelect::make()->id('s')->name('caches')
            ->allOption('All', mode: AllOptionMode::EachValue)->toHtml());

        expect($scalar->filter('.all input[type="checkbox"]')->attr('name'))->toBe('sources')
            ->and($eachValue->filter('.all input[type="checkbox"]')->attr('name'))->toBeNull()
            ->and($eachValue->filter('craft-checkbox-select')->attr('all-mode'))->toBe('each-value');
    });

    it('renders its sortable, presentation, and disabled options as attributes', function () {
        $host = new Crawler(CheckboxSelect::make()->id('s')
            ->sortable()
            ->presentation(CheckboxSelectPresentation::AddMenu)
            ->disabled()
            ->toHtml())->filter('craft-checkbox-select');

        expect($host->attr('sortable'))->not->toBeNull()
            ->and($host->attr('presentation'))->toBe('add-menu')
            ->and($host->attr('disabled'))->not->toBeNull()
            ->and(CheckboxSelect::make()->id('s')->toHtml())->not->toContain('presentation=');
    });
});
