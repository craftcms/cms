<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Data\LinkData;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\LinkTypes\Url as UrlType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;

test('localizing links preserves metadata and unavailable targets', function (bool $available, bool $propagating) {
    $site = Site::factory()->create();
    $entryType = EntryType::factory()->create();
    $sectionFactory = Section::factory()->withEntryTypes($entryType);
    if ($available) {
        $sectionFactory = $sectionFactory->withSites($site);
    }
    $section = $sectionFactory->create();
    $target = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement();
    expect(Elements::saveElement($target))->toBeTrue();
    $source = EntryModel::factory()->createElement();
    $owner = clone $source;
    $owner->siteId = $site->id;
    $owner->propagateAll = true;
    $owner->propagating = $propagating;
    if ($propagating) {
        $owner->propagatingFrom = $source;
    } else {
        $owner->duplicateOf = $source;
    }

    $field = new Link([
        'types' => ['entry'], 'translationMethod' => 'site', 'showLabelField' => true,
        'advancedFields' => ['urlSuffix', 'target', 'title', 'class', 'id', 'rel', 'ariaLabel', 'download'],
    ]);
    $metadata = [
        'label' => 'Custom label', 'urlSuffix' => '#section', 'target' => '_blank', 'title' => 'Title',
        'class' => 'button', 'id' => 'link', 'rel' => 'nofollow', 'ariaLabel' => 'Details', 'download' => true, 'filename' => 'guide.pdf',
    ];
    $original = $field->normalizeValue(['type' => 'entry', 'value' => "{entry:$target->id@$target->siteId:url}", ...$metadata], null);
    $localized = $field->normalizeValue($original, $owner);
    $expectedSiteId = $available ? $site->id : $target->siteId;

    expect($localized->serialize())->toBe([
        'value' => "{entry:$target->id@$expectedSiteId:url}", 'type' => 'entry', ...$metadata,
    ]);
    expect($original->serialize())->toBe([
        'value' => "{entry:$target->id@$target->siteId:url}", 'type' => 'entry', ...$metadata,
    ]);
    expect($localized->getElement()->siteId)->toBe($expectedSiteId);
    if (! $available) {
        expect($localized)->toBe($original);
    }
})->with([true, false])->with([true, false]);

test('link field validates shared link settings', function () {
    $field = new Link([
        'name' => 'Link',
        'handle' => 'link',
        'types' => ['url'],
        'advancedFields' => ['target', 'download'],
    ]);
    $invalidTypeField = new Link([
        'name' => 'Link',
        'handle' => 'link',
        'types' => ['missing'],
    ]);
    $invalidAdvancedField = new Link([
        'name' => 'Link',
        'handle' => 'link',
        'advancedFields' => ['missing'],
    ]);

    expect($field->validate(['types', 'typeSettings', 'showLabelField', 'advancedFields']))->toBeTrue()
        ->and($invalidTypeField->validate(['types']))->toBeFalse()
        ->and($invalidTypeField->errors()->has('types'))->toBeTrue()
        ->and($invalidAdvancedField->validate(['advancedFields']))->toBeFalse()
        ->and($invalidAdvancedField->errors()->has('advancedFields'))->toBeTrue();
});

test('link field validates max length', function () {
    $invalidResult = EntryModel::factory()
        ->withField('shortLink', Link::class, ['types' => ['url'], 'maxLength' => 5], value: new LinkData('https://example.com', new UrlType))
        ->createElementWithFields(save: false);
    $invalidResult->element->validate();

    expect($invalidResult->element->errors()->has('shortLink'))->toBeTrue();

    $validResult = EntryModel::factory()
        ->withField('normalLink', Link::class, ['types' => ['url'], 'maxLength' => 255], value: new LinkData('https://example.com', new UrlType))
        ->createElementWithFields(save: false);
    $validResult->element->validate();

    expect($validResult->element->errors()->has('normalLink'))->toBeFalse();
});
