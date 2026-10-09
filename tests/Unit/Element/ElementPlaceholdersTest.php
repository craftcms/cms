<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Operations\ElementPlaceholders;

it('stores placeholder elements by canonical id site id and uri', function () {
    $placeholders = new ElementPlaceholders;

    $canonical = placeholderElement(id: 100, canonicalId: 100, siteId: 1, uri: 'news/example');
    $derivative = placeholderElement(id: 200, canonicalId: 100, siteId: 2, uri: 'fr/nouvelles/exemple');

    $placeholders->setPlaceholderElement($canonical);
    $placeholders->setPlaceholderElement($derivative);

    expect($placeholders->getPlaceholderElement(100, 1))->toBe($canonical)
        ->and($placeholders->getPlaceholderElement(100, 2))->toBe($derivative)
        ->and($placeholders->getPlaceholderByUri('news/example', 1))->toBe($canonical)
        ->and($placeholders->getPlaceholderByUri('fr/nouvelles/exemple', 2))->toBe($derivative)
        ->and($placeholders->getPlaceholderElements())->toEqualCanonicalizing([$canonical, $derivative]);
});

it('returns null or an empty list when no matching placeholder exists', function () {
    $placeholders = new ElementPlaceholders;

    expect($placeholders->getPlaceholderElements())->toBe([])
        ->and($placeholders->getPlaceholderElement(999, 1))->toBeNull()
        ->and($placeholders->getPlaceholderByUri('missing', 1))->toBeNull();
});

it('throws when storing a placeholder without an id or site id', function () {
    $placeholders = new ElementPlaceholders;

    expect(fn () => $placeholders->setPlaceholderElement(placeholderElement(id: null, siteId: 1)))
        ->toThrow(InvalidArgumentException::class, 'Placeholder element is missing an ID');

    expect(fn () => $placeholders->setPlaceholderElement(placeholderElement(id: 100, siteId: null)))
        ->toThrow(InvalidArgumentException::class, 'Placeholder element is missing an ID');
});

function placeholderElement(?int $id = 100, ?int $canonicalId = null, ?int $siteId = 1, ?string $uri = null): TestPlaceholderElement
{
    $element = new TestPlaceholderElement;
    $element->id = $id;
    $element->siteId = $siteId;
    $element->uri = $uri;
    $element->setCanonicalId($canonicalId);

    return $element;
}

class TestPlaceholderElement extends Element
{
    #[Override]
    public static function displayName(): string
    {
        return 'Placeholder Test Element';
    }
}
