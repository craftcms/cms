<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Elements\ContentBlock as ContentBlockElement;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
});

/**
 * Reproduction test for https://github.com/craftcms/cms/issues/19543.
 *
 * `ContentBlock::_normalizeValueInternal()`'s batched lookup (used whenever the owner element was
 * fetched alongside other same-site elements in a single query -- which a batched resave triggers for
 * any batch that includes revisions) constrained its `ContentBlockElement` query with
 * `->revisions($element->getIsRevision())`. When the owner was a revision, that became an INNER JOIN
 * requiring the content block's OWN row to be flagged as a revision -- but a nested element isn't
 * necessarily forked into its own revision row just because its owner was revisioned; it may still be
 * the canonical row, merely relinked (via `elements_owners`) to the new revision-owned block entry. That
 * row's own `revisionId` stays null, so the batched lookup missed it entirely, and
 * `_normalizeValueInternal()` fell through to creating a brand new, empty `ContentBlockElement` in its
 * place.
 *
 * This test relinks a canonical (unforked) `ContentBlockElement` to a revision-owned Matrix block entry
 * -- the state confirmed occurring in production -- and shows the batched lookup finding the real,
 * populated content block instead of substituting an empty one.
 */
it('finds a nested content block for a revisioned matrix block entry via the batched lookup', function () {
    $innerField = Field::factory()->create([
        'name' => 'Block Text',
        'handle' => 'blockText',
        'type' => PlainText::class,
    ]);

    Fields::invalidateCaches();
    Fields::refreshFields();

    $layoutUid = Str::uuid()->toString();
    $contentBlockSettings = [
        'fieldLayouts' => [
            $layoutUid => [
                'tabs' => [
                    [
                        'uid' => Str::uuid()->toString(),
                        'name' => 'Content',
                        'elements' => [
                            [
                                'uid' => Str::uuid()->toString(),
                                'type' => CustomField::class,
                                'fieldUid' => $innerField->uid,
                                'required' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $contentBlockField = Field::factory()->create([
        'name' => 'Test Block',
        'handle' => 'testBlock',
        'type' => ContentBlock::class,
        'settings' => $contentBlockSettings,
    ]);

    Fields::invalidateCaches();
    Fields::refreshFields();

    // The block entry type used as a Matrix block type, with the ContentBlock field nested inside it --
    // reproducing #19543 requires the ContentBlock to be nested two levels deep (Page > Matrix block >
    // ContentBlock), not directly on a top-level entry.
    $blockEntryType = EntryTypeModel::factory()
        ->withField($contentBlockField)
        ->create([
            'name' => 'Test Matrix Block',
            'handle' => 'testMatrixBlock',
            'hasTitleField' => false,
            'titleFormat' => '{id}',
        ]);

    $result = EntryModel::factory()
        ->withField('testMatrix', Matrix::class, ['entryTypes' => [$blockEntryType->id]], value: [
            'new1' => [
                'type' => $blockEntryType->handle,
                'fields' => [
                    'testBlock' => ['fields' => ['blockText' => 'original content']],
                ],
            ],
        ])
        ->createElementWithFields();

    $page = $result->element;
    $primarySiteId = $page->siteId;

    $canonicalBlockEntry = EntryElement::find()
        ->ownerId($page->id)
        ->fieldId(Fields::getFieldByHandle('testMatrix')->id)
        ->siteId($primarySiteId)
        ->status(null)
        ->one();
    expect($canonicalBlockEntry)->not->toBeNull();

    /** @var ContentBlockElement $canonicalContentBlock */
    $canonicalContentBlock = $canonicalBlockEntry->getFieldValue('testBlock');
    expect($canonicalContentBlock->getFieldValue('blockText'))->toBe('original content');

    // Create a revision of the page. The nested matrix block entry gets its own genuine revision row.
    // `force` is needed because the entry's initial save already produced a revision (taken before the
    // matrix block existed); without it, `createRevision()` would just hand that stale one back.
    $revisionId = app(Revisions::class)->createRevision($page, force: true);

    $revisionBlockEntry = EntryElement::find()
        ->ownerId($revisionId)
        ->fieldId(Fields::getFieldByHandle('testMatrix')->id)
        ->siteId($primarySiteId)
        ->revisions(null)
        ->status(null)
        ->one();
    expect($revisionBlockEntry)->not->toBeNull();
    expect($revisionBlockEntry->getIsRevision())->toBeTrue();

    // Simulate the state confirmed occurring in production: the revision's nested ContentBlock never
    // got forked into its own revision row -- it's still the canonical row, simply relinked (via
    // `elements_owners`) to the revision-owned Matrix block entry. Do this by hard-deleting whichever
    // ContentBlock *did* get created for the revision block entry, and relinking the canonical
    // ContentBlock (whose own `revisionId` is null) in its place -- exactly what the nested element
    // manager itself does when it relinks a nested element instead of forking it.
    $forkedContentBlock = ContentBlockElement::find()
        ->fieldId($contentBlockField->id)
        ->ownerId($revisionBlockEntry->id)
        ->siteId($primarySiteId)
        ->revisions(null)
        ->status(null)
        ->one();
    if ($forkedContentBlock) {
        Elements::deleteElementById($forkedContentBlock->id, hardDelete: true);
    }

    DB::table(Table::ELEMENTS_OWNERS)->where([
        'elementId' => $canonicalContentBlock->id,
        'ownerId' => $revisionBlockEntry->id,
    ])->delete();
    DB::table(Table::ELEMENTS_OWNERS)->insert([
        'elementId' => $canonicalContentBlock->id,
        'ownerId' => $revisionBlockEntry->id,
        'sortOrder' => 1,
    ]);

    expect($canonicalContentBlock->revisionId)->toBeNull();

    // Reproduce a batched resave: query the canonical + revision Matrix block entries together in a
    // single call, so `ContentBlock::_normalizeValueInternal()` takes its batched-lookup code path
    // (populated by the element query's `afterPopulate()` on any multi-result query -- exactly what a
    // batched resave does when it processes a chunk of entries, revisions included).
    $batch = EntryElement::find()
        ->id([$canonicalBlockEntry->id, $revisionBlockEntry->id])
        ->siteId($primarySiteId)
        ->revisions(null)
        ->status(null)
        ->get();
    expect($batch)->toHaveCount(2);

    $batchedRevisionBlockEntry = $batch->first(fn (EntryElement $e) => $e->id === $revisionBlockEntry->id);
    expect($batchedRevisionBlockEntry)->not->toBeNull();

    // This is where #19543 bites: the batched lookup's `->revisions(true)` missed the relinked
    // canonical row, and a brand new, empty, unsaved ContentBlockElement was silently substituted for
    // the real one.
    $contentBlock = $batchedRevisionBlockEntry->getFieldValue('testBlock');
    expect($contentBlock->id)->not->toBeNull();
    expect($contentBlock->getFieldValue('blockText'))->toBe('original content');
});
