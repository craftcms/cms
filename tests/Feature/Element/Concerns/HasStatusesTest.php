<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Support\Facades\Sites;

class TestStatusElement extends Element
{
    public bool $customIsDraft = false;

    public bool $isProvisionalDraft = false;

    public bool $archived = false;

    public bool $enabled = true;

    #[Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }

    #[Override]
    public function getIsDraft(): bool
    {
        return $this->customIsDraft;
    }
}

beforeEach(function () {
    $this->primarySiteId = Sites::getPrimarySite()->id;
});

describe('getEnabledForSite', function () {
    test('returns true by default', function () {
        $element = new TestStatusElement;
        $element->siteId = $this->primarySiteId;

        expect($element->getEnabledForSite())->toBeTrue();
    });

    test('returns value for current site when set as boolean', function () {
        $element = new TestStatusElement;
        $element->siteId = $this->primarySiteId;
        $element->setEnabledForSite(false);

        expect($element->getEnabledForSite())->toBeFalse();
    });

    test('returns value for specific site from array', function () {
        $element = new TestStatusElement;
        $element->siteId = $this->primarySiteId;
        $element->setEnabledForSite([
            $this->primarySiteId => false,
            999 => true,
        ]);

        expect($element->getEnabledForSite($this->primarySiteId))->toBeFalse();
        expect($element->getEnabledForSite(999))->toBeTrue();
    });

    test('returns null for unknown site when using array', function () {
        $element = new TestStatusElement;
        $element->siteId = $this->primarySiteId;
        $element->setEnabledForSite([
            $this->primarySiteId => true,
        ]);

        expect($element->getEnabledForSite(12345))->toBeNull();
    });

    test('returns null for different site when set as boolean', function () {
        $element = new TestStatusElement;
        $element->siteId = $this->primarySiteId;
        $element->setEnabledForSite(true);

        expect($element->getEnabledForSite(12345))->toBeNull();
    });

    test('defaults to current siteId when no siteId parameter passed', function () {
        $element = new TestStatusElement;
        $element->siteId = $this->primarySiteId;
        $element->setEnabledForSite([
            $this->primarySiteId => false,
        ]);

        expect($element->getEnabledForSite())->toBeFalse();
    });
});

describe('setEnabledForSite', function () {
    test('accepts boolean value', function () {
        $element = new TestStatusElement;
        $element->siteId = $this->primarySiteId;

        $element->setEnabledForSite(false);
        expect($element->getEnabledForSite())->toBeFalse();

        $element->setEnabledForSite(true);
        expect($element->getEnabledForSite())->toBeTrue();
    });

    test('accepts array of site IDs', function () {
        $element = new TestStatusElement;
        $element->siteId = $this->primarySiteId;

        $element->setEnabledForSite([
            $this->primarySiteId => true,
            999 => false,
        ]);

        expect($element->getEnabledForSite($this->primarySiteId))->toBeTrue();
        expect($element->getEnabledForSite(999))->toBeFalse();
    });
});

describe('getStatus', function () {
    test('returns STATUS_ENABLED when enabled and enabled for site', function () {
        $element = new TestStatusElement;
        $element->siteId = $this->primarySiteId;
        $element->enabled = true;
        $element->setEnabledForSite(true);

        expect($element->getStatus())->toBe(Element::STATUS_ENABLED);
    });

    test('returns STATUS_DISABLED when not enabled', function () {
        $element = new TestStatusElement;
        $element->siteId = $this->primarySiteId;
        $element->enabled = false;

        expect($element->getStatus())->toBe(Element::STATUS_DISABLED);
    });

    test('returns STATUS_DISABLED when not enabled for site', function () {
        $element = new TestStatusElement;
        $element->siteId = $this->primarySiteId;
        $element->enabled = true;
        $element->setEnabledForSite(false);

        expect($element->getStatus())->toBe(Element::STATUS_DISABLED);
    });

    test('returns STATUS_ARCHIVED when archived', function () {
        $element = new TestStatusElement;
        $element->siteId = $this->primarySiteId;
        $element->archived = true;

        expect($element->getStatus())->toBe(Element::STATUS_ARCHIVED);
    });

    test('returns STATUS_DRAFT for non-provisional drafts', function () {
        $element = new TestStatusElement;
        $element->siteId = $this->primarySiteId;
        $element->customIsDraft = true;
        $element->isProvisionalDraft = false;

        expect($element->getStatus())->toBe(Element::STATUS_DRAFT);
    });

    test('does not return STATUS_DRAFT for provisional drafts', function () {
        $element = new TestStatusElement;
        $element->siteId = $this->primarySiteId;
        $element->customIsDraft = true;
        $element->isProvisionalDraft = true;
        $element->enabled = true;

        expect($element->getStatus())->toBe(Element::STATUS_ENABLED);
    });
});
