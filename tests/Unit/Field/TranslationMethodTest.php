<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Site\Data\Site;

class TestTranslationElement extends Element
{
    public Site $site;

    #[Override]
    public static function displayName(): string
    {
        return 'Translation Element';
    }

    #[Override]
    public function getSite(): Site
    {
        return $this->site;
    }
}

beforeEach(function () {
    $site = new Site;
    $site->id = 2;
    $site->groupId = 1;
    $site->handle = 'test-site';
    $site->language = 'en-US';

    $this->element = new TestTranslationElement;
    $this->element->siteId = 2;
    $this->element->site = $site;
});

test('returns translation descriptions', function () {
    expect(TranslationMethod::Site->description())->toBe('This field is translated for each site.')
        ->and(TranslationMethod::SiteGroup->description())->toBe('This field is translated for each site group.')
        ->and(TranslationMethod::Language->description())->toBe('This field is translated for each language.')
        ->and(TranslationMethod::None->description())->toBeNull();
});

test('returns translation keys for built in methods', function () {
    expect(TranslationMethod::None->elementKey($this->element))->toBe('1')
        ->and(TranslationMethod::Site->elementKey($this->element))->toBe('2')
        ->and(TranslationMethod::Language->elementKey($this->element))->toBe($this->element->getSite()->getLanguage())
        ->and(TranslationMethod::SiteGroup->elementKey($this->element))->toBe((string) $this->element->getSite()->groupId);
});

test('returns translation key for custom format', function () {
    expect(TranslationMethod::Custom->elementKey($this->element, '{siteId}-{site.handle}'))
        ->toBe(sprintf('%s-%s', $this->element->siteId, $this->element->getSite()->handle));
});
