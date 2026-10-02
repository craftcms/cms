<?php

declare(strict_types=1);

use craft\test\fixtures\elements\BaseContentFixture;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Models\User;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Illuminate\Support\Facades\Event;

uses(DatabaseTestCase::class);

beforeEach(function() {
    $this->actingAs(User::factory()->admin()->create());
});

/**
 * Tests the “Make element fixture revision creation opt-in” behavior added in
 * https://github.com/craftcms/cms/pull/19626.
 *
 * `craft\test\ElementFixtureTrait::saveElement()` suppresses revision creation by default, restoring
 * `enableVersioning` afterward, and lets fixtures opt back in via `$createRevisions`.
 */
/** @return bool Whether the entry has any saved revisions */
function elementFixtureHasRevisions(EntryElement $entry): bool
{
    return EntryElement::find()->revisionOf($entry)->site('*')->status(null)->exists();
}

/**
 * Returns whether the section currently has versioning enabled, per the live `Sections` cache — the
 * `Section` model instance a test holds onto is never itself mutated by the fixture trait, which flips
 * `enableVersioning` on the cached `CraftCms\Cms\Section\Data\Section` instead.
 */
function elementFixtureSectionHasVersioning(Section $section): bool
{
    return Sections::getSectionById($section->id)->enableVersioning;
}

it('doesn’t create revisions by default, and restores enableVersioning afterward', function() {
    $entryType = EntryTypeModel::factory()->create(['hasTitleField' => true]);
    $section = Section::factory()
        ->withEntryTypes($entryType)
        ->create([
            'type' => SectionType::Channel,
            'enableVersioning' => true,
        ]);
    Sections::refreshSections();

    $entryModel = EntryModel::factory()->forSection($section)->forEntryType($entryType)->create();
    $entry = EntryElement::find()->id($entryModel->id)->siteId($entryModel->siteId)->status(null)->one();

    $fixture = new class(['elementType' => EntryElement::class]) extends BaseContentFixture {
    };

    $entry->title = 'Updated via fixture';
    $saveElement = fn() => $this->saveElement($entry);
    expect($saveElement->call($fixture))->toBeTrue()
        ->and(elementFixtureSectionHasVersioning($section))->toBeTrue()
        ->and(elementFixtureHasRevisions($entry))->toBeFalse();

    // An ordinary save afterward should still create revisions normally.
    $entry->title = 'Saved outside the fixture';
    expect(Elements::saveElement($entry))->toBeTrue()
        ->and(elementFixtureHasRevisions($entry))->toBeTrue();
});

it('creates revisions when the fixture opts in via $createRevisions', function() {
    $entryType = EntryTypeModel::factory()->create(['hasTitleField' => true]);
    $section = Section::factory()
        ->withEntryTypes($entryType)
        ->create([
            'type' => SectionType::Channel,
            'enableVersioning' => true,
        ]);
    Sections::refreshSections();

    $entryModel = EntryModel::factory()->forSection($section)->forEntryType($entryType)->create();
    $entry = EntryElement::find()->id($entryModel->id)->siteId($entryModel->siteId)->status(null)->one();

    $fixture = new class(['elementType' => EntryElement::class]) extends BaseContentFixture {
    };
    $fixture->createRevisions = true;

    $entry->title = 'Updated via fixture';
    $saveElement = fn() => $this->saveElement($entry);
    expect($saveElement->call($fixture))->toBeTrue()
        ->and(elementFixtureSectionHasVersioning($section))->toBeTrue()
        ->and(elementFixtureHasRevisions($entry))->toBeTrue();
});

it('restores enableVersioning even when the fixture save fails', function() {
    $entryType = EntryTypeModel::factory()->create(['hasTitleField' => true]);
    $section = Section::factory()
        ->withEntryTypes($entryType)
        ->create([
            'type' => SectionType::Channel,
            'enableVersioning' => true,
        ]);
    Sections::refreshSections();

    $entryModel = EntryModel::factory()->forSection($section)->forEntryType($entryType)->create();
    $entry = EntryElement::find()->id($entryModel->id)->siteId($entryModel->siteId)->status(null)->one();

    $fixture = new class(['elementType' => EntryElement::class]) extends BaseContentFixture {
    };

    // Registered before the fixture trait's own listener, so it fires first and simply rejects the save;
    // the interesting assertion is what happens to `enableVersioning` afterward (see below).
    $listener = static function(ElementSaving $event) use ($entry): void {
        if ($event->element === $entry) {
            $event->isValid = false;
        }
    };
    Event::listen(ElementSaving::class, $listener);

    $entry->title = 'This save should be rejected';
    $saveElement = fn() => $this->saveElement($entry);
    expect($saveElement->call($fixture))->toBeFalse()
        ->and(elementFixtureSectionHasVersioning($section))->toBeTrue();
});

it('suppresses versioning independently on a nested Matrix block saved by the fixture', function() {
    $blockTextField = Field::factory()->create([
        'name' => 'Block Text',
        'handle' => 'blockText',
        'type' => PlainText::class,
    ]);
    Fields::refreshFields();

    $blockEntryType = EntryTypeModel::factory()
        ->withField($blockTextField)
        ->create(['hasTitleField' => false, 'titleFormat' => '{id}']);
    EntryTypes::refreshEntryTypes();

    $matrixField = Field::factory()->create([
        'name' => 'Test Matrix',
        'handle' => 'testMatrix',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$blockEntryType->id], 'enableVersioning' => true],
    ]);
    Fields::refreshFields();

    $pageEntryType = EntryTypeModel::factory()->withField($matrixField)->create(['hasTitleField' => true]);
    EntryTypes::refreshEntryTypes();

    $section = Section::factory()
        ->withEntryTypes($pageEntryType)
        ->create(['type' => SectionType::Channel, 'enableVersioning' => true]);
    Sections::refreshSections();

    $pageModel = EntryModel::factory()->forSection($section)->forEntryType($pageEntryType)->create();
    $page = EntryElement::find()->id($pageModel->id)->siteId($pageModel->siteId)->status(null)->one();
    $page->title = 'Page with a versioned Matrix field';
    $page->setFieldValue('testMatrix', [
        'new1' => [
            'type' => $blockEntryType->handle,
            'fields' => ['blockText' => 'original content'],
        ],
    ]);

    $fixture = new class(['elementType' => EntryElement::class]) extends BaseContentFixture {
    };
    $saveElement = fn() => $this->saveElement($page);
    expect($saveElement->call($fixture))->toBeTrue();

    /** @var Matrix $refreshedMatrixField */
    $refreshedMatrixField = Fields::getFieldByHandle('testMatrix');
    expect($refreshedMatrixField->enableVersioning)->toBeTrue();

    $block = EntryElement::find()
        ->ownerId($page->id)
        ->fieldId($matrixField->id)
        ->siteId($pageModel->siteId)
        ->status(null)
        ->one();
    expect($block)->not->toBeNull()
        ->and(elementFixtureHasRevisions($block))->toBeFalse();

    // A save outside the fixture should create a revision for the block normally.
    $block->setFieldValue('blockText', 'saved outside the fixture');
    expect(Elements::saveElement($block))->toBeTrue()
        ->and(elementFixtureHasRevisions($block))->toBeTrue();
});
