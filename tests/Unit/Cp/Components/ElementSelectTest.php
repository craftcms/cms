<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cp\Components\ElementSelect;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use Symfony\Component\DomCrawler\Crawler;

function elementSelectSettings(ElementSelect $select): array
{
    return Json::decode(new Crawler($select->toHtml())->filter('.elementselect')->attr('settings'));
}

it('picks the custom element for the element type', function (string $elementType, string $tag) {
    expect(ElementSelect::make()->elementType($elementType)->toHtml())->toContain("<$tag ");
})->with([
    'entries' => [Entry::class, 'craft-entry-select-input'],
    'assets' => [Asset::class, 'craft-asset-select-input'],
    'other elements' => [User::class, 'craft-element-select-input'],
]);

it('renders the empty submission value, chips list and choose button', function () {
    $crawler = new Crawler(ElementSelect::make()
        ->id('authors')
        ->name('authorIds')
        ->elementType(User::class)
        ->selectionLabel('Add an author')
        ->attributes(['class' => 'extra'])
        ->toHtml());

    $host = $crawler->filter('craft-element-select-input#authors.elementselect.extra');

    expect($host)->toHaveCount(1)
        ->and($host->filter(':scope > input[type="hidden"][name="authorIds"][value=""]'))->toHaveCount(1)
        ->and($host->filter(':scope > ul.elements'))->toHaveCount(1)
        ->and($host->filter('craft-button.add[command="--add-element"]')->text())->toBe('Add an author');
});

it('passes its settings to the JS class', function () {
    $settings = elementSelectSettings(ElementSelect::make()
        ->id('parentId')
        ->name('parentId')
        ->elementType(Entry::class)
        ->sources(['section:abc'])
        ->limit(1)
        ->selectionLabel('Choose a parent')
        ->modalSettings(['defaultSiteId' => 2])
        ->settings(['canUpload' => true]));

    expect($settings)->toMatchArray([
        'id' => 'parentId',
        'name' => 'parentId',
        'elementType' => Entry::class,
        'sources' => ['section:abc'],
        'limit' => 1,
        'sortable' => false,
        'editable' => true,
        'canUpload' => true,
        'modalSettings' => ['modalTitle' => 'Choose a parent', 'defaultSiteId' => 2],
    ]);
});

it('boots a custom JS class only when it isn’t a default one', function (?string $jsClass, ?string $expected) {
    $html = ElementSelect::make()->elementType(Entry::class)->inputClass($jsClass)->toHtml();
    $host = new Crawler($html)->filter('.elementselect');

    expect($host->attr('input-class'))->toBe($expected);
})->with([
    'none' => [null, null],
    'default' => ['Craft.EntrySelectInput', null],
    'custom' => ['Craft.CategorySelectInput', 'Craft.CategorySelectInput'],
]);

it('renders a search input for search criteria', function () {
    $html = ElementSelect::make()->elementType(User::class)->searchCriteria(['status' => null])->labelId('authors-label')->toHtml();

    expect($html)->toContainTag('input', ['role' => 'combobox', 'aria-labelledby' => 'authors-label']);
});

it('disables selection and the button when disabled', function () {
    $select = ElementSelect::make()->elementType(User::class)->disabled();

    expect($select->toHtml())->toContainTag('craft-button', ['disabled' => true])
        ->and(elementSelectSettings($select))->toMatchArray(['selectable' => false, 'editable' => false]);
});
