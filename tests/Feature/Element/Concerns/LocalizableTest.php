<?php

declare(strict_types=1);

use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Support\Facades\Sites;
use yii\base\InvalidConfigException;

class TestLocalizableElement extends Element
{
    #[Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }

    #[\Override]
    public static function find(): ElementQuery
    {
        return new ElementQuery(static::class);
    }
}

class TestNestedLocalizableElement extends TestLocalizableElement implements NestedElementInterface
{
    public ?TestLocalizableElement $owner = null;

    public function getOwner(): ?Element
    {
        return $this->owner;
    }

    public function getPrimaryOwnerId(): ?int
    {
        return null;
    }

    public function setPrimaryOwnerId(?int $id): void
    {
        //
    }

    public function getPrimaryOwner(): ?ElementInterface
    {
        return null;
    }

    public function setPrimaryOwner(?ElementInterface $owner): void
    {
        //
    }

    public function getOwnerId(): ?int
    {
        return null;
    }

    public function setOwnerId(?int $id): void
    {
        //
    }

    public function setOwner(?ElementInterface $owner): void
    {
        /** @var TestLocalizableElement|null $owner */
        $this->owner = $owner;
    }

    public function getOwners(array $criteria = []): array
    {
        return [];
    }

    public function getField(): ?ElementContainerFieldInterface
    {
        return null;
    }

    public function getSortOrder(): ?int
    {
        return null;
    }

    public function setSortOrder(?int $sortOrder): void
    {
        //
    }

    public function setSaveOwnership(bool $saveOwnership): void
    {
        //
    }
}

describe('getRootOwner', function () {
    test('returns self if not nested', function () {
        $element = new TestLocalizableElement;
        expect($element->getRootOwner())->toBe($element);
    });

    test('returns owner if nested', function () {
        $owner = new TestLocalizableElement;
        $element = new TestNestedLocalizableElement;
        $element->owner = $owner;

        expect($element->getRootOwner())->toBe($owner);
    });

    test('returns root owner if deeply nested', function () {
        $root = new TestLocalizableElement;
        $middle = new TestNestedLocalizableElement;
        $middle->owner = $root;
        $element = new TestNestedLocalizableElement;
        $element->owner = $middle;

        expect($element->getRootOwner())->toBe($root);
    });
});

describe('getLocalized', function () {
    test('returns element query excluding current site', function () {
        $element = new TestLocalizableElement;
        $element->id = 123;
        $element->siteId = Sites::getPrimarySite()->id;

        $query = $element->getLocalized();

        expect($query)->toBeInstanceOf(ElementQuery::class)
            ->and($query->id)->toBe(123);
    });

    test('returns eager loaded elements if available', function () {
        $element = new TestLocalizableElement;
        $localized = new ElementCollection([new TestLocalizableElement]);

        $ref = new ReflectionClass(Element::class);
        $prop = $ref->getProperty('_eagerLoadedElements');
        $prop->setValue($element, ['localized' => $localized]);

        expect($element->getLocalized())->toBe($localized);
    });
});

describe('getSite', function () {
    test('returns site object', function () {
        $primarySiteId = Sites::getPrimarySite()->id;
        $element = new TestLocalizableElement;
        $element->siteId = $primarySiteId;

        $site = $element->getSite();
        expect($site->id)->toBe($primarySiteId);
    });

    test('throws exception for invalid site id', function () {
        $element = new TestLocalizableElement;
        $element->siteId = 99999;

        expect(fn () => $element->getSite())->toThrow(InvalidConfigException::class);
    });
});

describe('getLanguage', function () {
    test('returns site language', function () {
        $primarySite = Sites::getPrimarySite();
        $element = new TestLocalizableElement;
        $element->siteId = $primarySite->id;

        expect($element->getLanguage())->toBe($primarySite->language);
    });
});

describe('getIsCrossSiteCopyable', function () {
    test('returns boolean', function () {
        $element = new TestLocalizableElement;
        expect($element->getIsCrossSiteCopyable())->toBeBool();
    });
});

describe('Translation Support', function () {
    test('getIsTitleTranslatable defaults to true', function () {
        $element = new TestLocalizableElement;
        expect($element->getIsTitleTranslatable())->toBeTrue();
    });

    test('getIsSlugTranslatable defaults to true', function () {
        $element = new TestLocalizableElement;
        expect($element->getIsSlugTranslatable())->toBeTrue();
    });

    test('translation descriptions are strings', function () {
        $element = new TestLocalizableElement;
        expect($element->getTitleTranslationDescription())->toBeString()
            ->and($element->getSlugTranslationDescription())->toBeString();
    });
});
