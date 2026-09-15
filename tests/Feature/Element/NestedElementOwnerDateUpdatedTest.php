<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Revisions;
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
use Illuminate\Support\Sleep;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::factory()->admin()->create());
});

/**
 * Reproduction test for https://github.com/craftcms/cms/issues/19594.
 *
 * When a nested element (e.g. a Matrix block entry) is saved on its own — independently of its owner —
 * only that element’s own `dateUpdated` timestamp gets updated. None of its ancestors’ `dateUpdated`
 * timestamps changed, even though their nested content did.
 *
 * `CraftCms\Cms\Element\Revisions::createRevision()` decides whether an element actually needs a new
 * revision by comparing its `dateUpdated` timestamp to the `dateCreated` timestamp of its most recent
 * revision. Since a deeply-nested edit never touched the ancestors’ `dateUpdated` values, that freshness
 * check found nothing changed at every ancestor level and short-circuited — reusing the existing (stale)
 * revisions instead of creating fresh ones that reflect the nested change.
 *
 * This test builds a two-levels-deep Matrix structure (Page > outer Matrix block > inner Matrix block),
 * establishes an initial revision, edits the innermost block directly (as if via its own edit page,
 * without resaving the Page), and confirms that a subsequently-created Page revision is a genuinely new
 * revision that captures the updated nested content — rather than reusing the stale prior revision.
 */
it('bumps every ancestor’s dateUpdated when a doubly-nested Matrix block is saved on its own', function () {
    $blockTextField = Field::factory()->create([
        'name' => 'Block Text',
        'handle' => 'blockText',
        'type' => PlainText::class,
    ]);
    Fields::refreshFields();

    // The entry type used as the *inner* Matrix block type: Page > outer Matrix > inner Matrix > this.
    $innerBlockEntryType = EntryTypeModel::factory()
        ->withField($blockTextField)
        ->create([
            'name' => 'Test Inner Block',
            'handle' => 'testInnerBlock',
            'hasTitleField' => false,
            'titleFormat' => '{id}',
        ]);
    EntryTypes::refreshEntryTypes();

    $innerMatrixField = Field::factory()->create([
        'name' => 'Test Inner Matrix',
        'handle' => 'testInnerMatrix',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$innerBlockEntryType->id]],
    ]);
    Fields::refreshFields();

    // The entry type used as the *outer* Matrix block type: Page > outer Matrix > this.
    $outerBlockEntryType = EntryTypeModel::factory()
        ->withField($innerMatrixField)
        ->create([
            'name' => 'Test Outer Block',
            'handle' => 'testOuterBlock',
            'hasTitleField' => false,
            'titleFormat' => '{id}',
        ]);
    EntryTypes::refreshEntryTypes();

    $outerMatrixField = Field::factory()->create([
        'name' => 'Test Outer Matrix',
        'handle' => 'testOuterMatrix',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$outerBlockEntryType->id]],
    ]);
    Fields::refreshFields();

    $pageEntryType = EntryTypeModel::factory()
        ->withField($outerMatrixField)
        ->create([
            'name' => 'Test Page',
            'handle' => 'testPage',
            'hasTitleField' => true,
        ]);
    EntryTypes::refreshEntryTypes();

    $section = Section::factory()
        ->withEntryTypes($pageEntryType)
        ->create([
            'type' => SectionType::Channel,
            'enableVersioning' => true,
        ]);
    Sections::refreshSections();

    $pageModel = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($pageEntryType)
        ->create();
    $primarySiteId = $pageModel->siteId;

    // 1. Create a page with a nested (outer Matrix > inner Matrix > plain text) structure.
    $page = EntryElement::find()->id($pageModel->id)->siteId($primarySiteId)->status(null)->one();
    $page->title = 'Test Page';
    $page->setFieldValue('testOuterMatrix', [
        'new1' => [
            'type' => 'testOuterBlock',
            'fields' => [
                'testInnerMatrix' => [
                    'new1' => [
                        'type' => 'testInnerBlock',
                        'fields' => ['blockText' => 'original content'],
                    ],
                ],
            ],
        ],
    ]);
    expect(Elements::saveElement($page))->toBeTrue();

    $outerBlockEntry = EntryElement::find()
        ->ownerId($page->id)
        ->fieldId($outerMatrixField->id)
        ->siteId($primarySiteId)
        ->status(null)
        ->one();
    expect($outerBlockEntry)->not->toBeNull();

    $innerBlockEntry = EntryElement::find()
        ->ownerId($outerBlockEntry->id)
        ->fieldId($innerMatrixField->id)
        ->siteId($primarySiteId)
        ->status(null)
        ->one();
    expect($innerBlockEntry)->not->toBeNull()
        ->and($innerBlockEntry->getFieldValue('blockText'))->toBe('original content');

    // 2. Establish an initial revision for the page, which cascades down and creates matching
    // revisions for both nested block levels.
    $revisionId1 = app(Revisions::class)->createRevision($page, force: true);

    $originalPageDateUpdated = $page->dateUpdated;
    $originalOuterBlockDateUpdated = $outerBlockEntry->dateUpdated;

    // Make sure the upcoming edit gets a distinct (later) `dateUpdated` timestamp, since timestamps
    // are only stored with second precision.
    Sleep::sleep(1);

    // 3. Edit the *innermost* block directly and save only that element — exactly as if it were
    // edited via its own nested-entry edit page, without resaving the Page or the outer block.
    $innerBlockEntry->setFieldValue('blockText', 'updated content');
    expect(Elements::saveElement($innerBlockEntry))->toBeTrue();

    // 4. The fix: saving the canonical inner block should have bumped `dateUpdated` on both of its
    // ancestors, recursively, even though neither was directly touched.
    $refetchedOuterBlockEntry = EntryElement::find()->id($outerBlockEntry->id)->siteId($primarySiteId)->status(null)->one();
    expect($refetchedOuterBlockEntry)->not->toBeNull()
        ->and($refetchedOuterBlockEntry->dateUpdated->getTimestamp())->toBeGreaterThan($originalOuterBlockDateUpdated->getTimestamp());

    $refetchedPage = EntryElement::find()->id($page->id)->siteId($primarySiteId)->status(null)->one();
    expect($refetchedPage)->not->toBeNull()
        ->and($refetchedPage->dateUpdated->getTimestamp())->toBeGreaterThan($originalPageDateUpdated->getTimestamp());

    // 5. Requesting a new revision for the page (using the freshly-refetched element, just like a
    // real, separate request would) should now create a *genuinely new* revision...
    $revisionId2 = app(Revisions::class)->createRevision($refetchedPage);
    expect($revisionId2)->not->toBe($revisionId1);

    // 6. ...and that new revision’s nested content should reflect the update, all the way down.
    $revisionOuterBlockEntry = EntryElement::find()
        ->ownerId($revisionId2)
        ->fieldId($outerMatrixField->id)
        ->siteId($primarySiteId)
        ->revisions(null)
        ->status(null)
        ->one();
    expect($revisionOuterBlockEntry)->not->toBeNull();

    $revisionInnerBlockEntry = EntryElement::find()
        ->ownerId($revisionOuterBlockEntry->id)
        ->fieldId($innerMatrixField->id)
        ->siteId($primarySiteId)
        ->revisions(null)
        ->status(null)
        ->one();
    expect($revisionInnerBlockEntry)->not->toBeNull()
        ->and($revisionInnerBlockEntry->getFieldValue('blockText'))->toBe('updated content');
});
