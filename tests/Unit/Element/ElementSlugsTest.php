<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementHelper;

test('generates temporary slug', function () {
    $slug = ElementHelper::tempSlug();

    expect($slug)->toStartWith('__temp_')
        ->and(ElementHelper::isTempSlug($slug))->toBeTrue();
});

test('normalizes slug using configured separator', function () {
    $glue = Cms::config()->slugWordSeparator;

    expect(ElementHelper::normalizeSlug('Audi S8 4E (2006-2010)'))
        ->toBe(implode($glue, ['audi', 's8', '4e', '2006-2010']));
});

test('keeps homepage uri unchanged', function () {
    expect(ElementHelper::normalizeSlug(Element::HOMEPAGE_URI))->toBe(Element::HOMEPAGE_URI);
});

test('generate slug converts separators before normalization', function () {
    $glue = Cms::config()->slugWordSeparator;

    expect(ElementHelper::generateSlug('A-B-C'))->toBe(implode($glue, ['a', 'b', 'c']));
});
