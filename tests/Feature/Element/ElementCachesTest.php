<?php

declare(strict_types=1);

use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Support\Facades\ElementCaches as ElementCachesFacade;

class TestElementCachesElement extends Element
{
    protected array $customCacheTags = [];

    #[Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }

    #[Override]
    public static function find(): ElementQuery
    {
        return new ElementQuery(static::class);
    }

    public function setCustomCacheTags(array $tags): void
    {
        $this->customCacheTags = $tags;
    }

    #[Override]
    protected function cacheTags(): array
    {
        return $this->customCacheTags;
    }
}

class TestNestedElementCachesElement extends TestElementCachesElement implements NestedElementInterface
{
    public ?ElementInterface $owner = null;

    public function getOwner(): ?ElementInterface
    {
        return $this->owner;
    }

    public function getPrimaryOwnerId(): ?int
    {
        return null;
    }

    public function setPrimaryOwnerId(?int $id): void {}

    public function getPrimaryOwner(): ?ElementInterface
    {
        return null;
    }

    public function setPrimaryOwner(?ElementInterface $owner): void {}

    public function getOwnerId(): ?int
    {
        return $this->owner?->id;
    }

    public function setOwnerId(?int $id): void {}

    public function setOwner(?ElementInterface $owner): void
    {
        $this->owner = $owner;
    }

    public function getOwners(array $criteria = []): array
    {
        return $this->owner ? [$this->owner] : [];
    }

    public function getField(): ?ElementContainerFieldInterface
    {
        return null;
    }

    public function getSortOrder(): ?int
    {
        return null;
    }

    public function setSortOrder(?int $sortOrder): void {}

    public function setSaveOwnership(bool $saveOwnership): void {}
}

beforeEach(function () {
    $this->elementCaches = app(ElementCaches::class);
});

it('is scoped within the current request and is available via the facade', function () {
    expect(app(ElementCaches::class))->toBe(app(ElementCaches::class));

    expect($this->elementCaches)->toBe(ElementCachesFacade::getFacadeRoot());
});

it('collects cache info through the extracted service', function () {
    $this->elementCaches->startCollectingCacheInfo();

    expect($this->elementCaches->isCollectingCacheInfo())->toBeTrue();

    $element = new TestElementCachesElement;
    $element->id = 123;

    $this->elementCaches->collectCacheTags(['foo']);
    $this->elementCaches->collectCacheInfoForElement($element);

    [$dependency] = $this->elementCaches->stopCollectingCacheInfo();

    expect($dependency?->tags)->toBe([
        'foo',
        'element',
        'element::'.TestElementCachesElement::class,
        'element::123',
    ]);
});

it('invalidates cached queries for an element type', function () {
    EntryModel::factory()->create();

    expect(entryQuery()->cache()->count())->toBe(1);

    EntryModel::factory()->create();

    expect(entryQuery()->cache()->count())->toBe(1);

    $this->elementCaches->invalidateForElementType(Entry::class);

    expect(entryQuery()->cache()->count())->toBe(2);
});

it('normalizes custom cache tags for an element', function () {
    $element = new TestElementCachesElement;
    $element->id = 123;
    $element->setCustomCacheTags(['custom', 'element::already-prefixed']);

    $tags = $this->elementCaches->invalidateForElement($element);

    expect($tags)->toBe([
        'element::'.TestElementCachesElement::class.'::*',
        'element::123',
        'element::'.TestElementCachesElement::class.'::custom',
        'element::already-prefixed',
    ]);
});

it('includes owner tags for nested elements', function () {
    $owner = new TestElementCachesElement;
    $owner->id = 456;

    $element = new TestNestedElementCachesElement;
    $element->id = 123;
    $element->setOwner($owner);
    $element->setCustomCacheTags(['custom']);

    $tags = $this->elementCaches->invalidateForElement($element);

    expect($tags)->toBe([
        'element::'.TestNestedElementCachesElement::class.'::*',
        'element::123',
        'element::456',
        'element::'.TestNestedElementCachesElement::class.'::custom',
    ]);
});

it('uses draft and revision invalidation tags instead of custom tags', function (callable $configureOwner, string $expectedTag) {
    $owner = new TestElementCachesElement;
    $owner->id = 456;
    $configureOwner($owner);

    $element = new TestNestedElementCachesElement;
    $element->id = 123;
    $element->setOwner($owner);
    $element->setCustomCacheTags(['custom']);

    $tags = $this->elementCaches->invalidateForElement($element);

    expect($tags)->toBe([
        'element::'.TestNestedElementCachesElement::class.'::*',
        'element::123',
        'element::456',
        $expectedTag,
    ]);
})->with([
    'draft root' => [
        function (TestElementCachesElement $owner): void {
            $owner->draftId = 1;
        },
        'element::'.TestNestedElementCachesElement::class.'::drafts',
    ],
    'revision root' => [
        function (TestElementCachesElement $owner): void {
            $owner->revisionId = 1;
        },
        'element::'.TestNestedElementCachesElement::class.'::revisions',
    ],
]);
