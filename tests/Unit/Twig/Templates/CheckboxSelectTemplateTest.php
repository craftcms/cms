<?php

declare(strict_types=1);

use CraftCms\Cms\View\TemplateMode;
use Symfony\Component\DomCrawler\Crawler;

use function CraftCms\Cms\renderString;

it('renders the element from the legacy checkboxSelect variables', function () {
    $crawler = new Crawler(renderString(
        "{% include '_includes/forms/checkboxSelect' with {id: 's', name: 'sources', options: {a: 'Alpha', b: 'Beta'}, values: ['b']} only %}",
        templateMode: TemplateMode::Cp,
    ));

    expect($crawler->filter('craft-checkbox-select#s.cp-checkbox-select'))->toHaveCount(1)
        ->and($crawler->filter('input[type="hidden"][data-empty]')->attr('name'))->toBe('sources')
        ->and($crawler->filter('.cp-checkbox-select__item input[name="sources[]"]')->extract(['value']))->toBe(['a', 'b'])
        ->and($crawler->filter('input[value="b"]')->attr('checked'))->not->toBeNull();
});

it('posts only the “All” value while the “All” option is checked', function () {
    $crawler = new Crawler(renderString(
        "{% include '_includes/forms/checkboxSelect' with {id: 's', name: 'sources', options: {a: 'Alpha'}, values: '*', showAllOption: true} only %}",
        templateMode: TemplateMode::Cp,
    ));
    $item = $crawler->filter('.cp-checkbox-select__item:not(.all) input[type="checkbox"]');

    expect($crawler->filter('.all input[type="checkbox"]')->attr('name'))->toBe('sources')
        ->and($crawler->filter('.all input[type="checkbox"]')->attr('checked'))->not->toBeNull()
        ->and($item->attr('checked'))->not->toBeNull()
        ->and($item->attr('name'))->toBeNull()
        ->and($item->attr('data-governed-name'))->toBe('sources[]')
        ->and($item->attr('disabled'))->toBeNull();
});

it('pre-orders sortable options by the values order, with the “Add item” menu', function () {
    $html = renderString(
        "{% include '_includes/forms/checkboxSelect' with {id: 's', name: 'sources', options: {a: 'Alpha', b: 'Beta'}, values: ['b', 'a'], sortable: true} only %}",
        templateMode: TemplateMode::Cp,
    );
    $host = new Crawler($html)->filter('craft-checkbox-select');

    expect($host->attr('sortable'))->not->toBeNull()
        ->and($host->attr('presentation'))->toBe('add-menu')
        ->and(strpos($html, 'value="b"'))->toBeLessThan(strpos($html, 'value="a"'));
});

it('takes a presentation for sortable options', function () {
    $host = new Crawler(renderString(
        "{% include '_includes/forms/checkboxSelect' with {id: 's', name: 'sources', options: {a: 'Alpha'}, sortable: true, presentation: 'checkboxes'} only %}",
        templateMode: TemplateMode::Cp,
    ))->filter('craft-checkbox-select');

    expect($host->attr('sortable'))->not->toBeNull()
        ->and($host->attr('presentation'))->toBeNull();
});
