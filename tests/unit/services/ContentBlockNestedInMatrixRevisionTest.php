<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\db\Table;
use craft\elements\ContentBlock as ContentBlockElement;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fields\ContentBlock;
use craft\fields\Matrix;
use craft\fields\PlainText;
use craft\helpers\Db;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\services\Elements;
use craft\test\TestCase;
use crafttests\fixtures\SitesFixture;
use RuntimeException;

/**
 * Reproduction test for https://github.com/craftcms/cms/issues/19543
 *
 * `ContentBlock::_normalizeValueInternal()`'s batched lookup (used whenever the owner element was
 * fetched alongside other same-site elements in a single query -- which `ResaveElements` triggers for
 * any batch that includes revisions) constrains its `ContentBlockElement` query with
 * `->revisions($element->getIsRevision())`:
 *
 * @see \craft\fields\ContentBlock::_normalizeValueInternal()
 *
 * When the owner is a revision, that becomes `->revisions(true)`, an INNER JOIN requiring the content
 * block's OWN row to have a `revisionId` set. But a nested element isn't necessarily forked into its own
 * revision row just because its owner was revisioned -- `NestedElementManager`/`Revisions::createRevision()`
 * can instead leave an unchanged nested element as the plain canonical row, merely relinked (via
 * `elements_owners`) to the new revision-owned block entry. That row's own `revisionId` stays null, so
 * the batched lookup misses it entirely, and `_normalizeValueInternal()` falls through to creating a
 * brand new, empty `ContentBlockElement` in its place. When that empty element is later saved,
 * `NestedElementManager::deleteOtherNestedElements()` soft-deletes the real, populated canonical block.
 *
 * The single-owner code path (`createContentBlockQuery()`) doesn't have this problem: when the owner is
 * a revision, it uses `->revisions(null)->trashed(null)`, matching either a genuine revision row or a
 * relinked canonical one. This test relinks a canonical (unforked) `ContentBlockElement` to a
 * revision-owned Matrix block entry -- the state the issue's author confirmed occurring in production --
 * and shows the batched lookup missing content that the single-owner path would have found.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class ContentBlockNestedInMatrixRevisionTest extends TestCase
{
    protected Elements $elements;
    private PlainText $blockTextField;
    private ContentBlock $contentBlockField;
    private EntryType $blockEntryType;
    private Matrix $matrixField;
    private EntryType $pageEntryType;
    private Section $section;

    /**
     * @inheritdoc
     */
    public function _fixtures(): array
    {
        return [
            'sites' => [
                'class' => SitesFixture::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();
        $this->elements = Craft::$app->getElements();

        $primarySiteId = Craft::$app->getSites()->getPrimarySite()->id;

        $this->blockTextField = new PlainText();
        $this->blockTextField->name = 'Block Text';
        $this->blockTextField->handle = 'blockText';
        if (!Craft::$app->getFields()->saveField($this->blockTextField)) {
            throw new RuntimeException('Could not save block text field.');
        }

        $this->contentBlockField = new ContentBlock();
        $this->contentBlockField->name = 'Test Block';
        $this->contentBlockField->handle = 'testBlock';
        $this->contentBlockField->setFieldLayout(
            $this->_makeFieldLayout(ContentBlockElement::class, $this->blockTextField)
        );
        if (!Craft::$app->getFields()->saveField($this->contentBlockField)) {
            throw new RuntimeException('Could not save content block field.');
        }

        // The entry type used as a Matrix block type, with the ContentBlock field nested inside it --
        // reproducing #19543 requires the ContentBlock to be nested two levels deep (Page > Matrix block
        // > ContentBlock), not directly on a top-level entry type.
        $this->blockEntryType = new EntryType();
        $this->blockEntryType->name = 'Test Matrix Block';
        $this->blockEntryType->handle = 'testMatrixBlock';
        $this->blockEntryType->hasTitleField = false;
        $this->blockEntryType->titleFormat = '{id}';
        $this->blockEntryType->setFieldLayout(
            $this->_makeFieldLayout(Entry::class, $this->contentBlockField)
        );
        if (!Craft::$app->getEntries()->saveEntryType($this->blockEntryType)) {
            throw new RuntimeException('Could not save block entry type.');
        }

        $this->matrixField = new Matrix();
        $this->matrixField->name = 'Test Matrix';
        $this->matrixField->handle = 'testMatrix';
        $this->matrixField->setEntryTypes([$this->blockEntryType]);
        if (!Craft::$app->getFields()->saveField($this->matrixField)) {
            throw new RuntimeException('Could not save matrix field.');
        }

        $this->pageEntryType = new EntryType();
        $this->pageEntryType->name = 'Test Page';
        $this->pageEntryType->handle = 'testPage';
        $this->pageEntryType->hasTitleField = true;
        $this->pageEntryType->setFieldLayout(
            $this->_makeFieldLayout(Entry::class, $this->matrixField)
        );
        if (!Craft::$app->getEntries()->saveEntryType($this->pageEntryType)) {
            throw new RuntimeException('Could not save page entry type.');
        }

        $this->section = new Section();
        $this->section->name = 'Test Page Section';
        $this->section->handle = 'testPageSection';
        $this->section->type = Section::TYPE_CHANNEL;
        $this->section->enableVersioning = true;
        $this->section->setEntryTypes([$this->pageEntryType]);
        $this->section->setSiteSettings([
            new Section_SiteSettings([
                'siteId' => $primarySiteId,
                'enabledByDefault' => true,
                'hasUrls' => false,
            ]),
        ]);
        if (!Craft::$app->getEntries()->saveSection($this->section)) {
            throw new RuntimeException('Could not save section.');
        }
    }

    /**
     * Builds a single-tab field layout containing a single custom field.
     */
    private function _makeFieldLayout(string $type, \craft\base\FieldInterface $field): FieldLayout
    {
        $fieldLayout = new FieldLayout(['type' => $type]);
        $tab = new FieldLayoutTab(['name' => 'Content']);
        $tab->setLayout($fieldLayout);
        $tab->setElements([new CustomField($field)]);
        $fieldLayout->setTabs([$tab]);
        return $fieldLayout;
    }

    /**
     * @inheritdoc
     */
    protected function _after(): void
    {
        Craft::$app->getEntries()->deleteSection($this->section);
        Craft::$app->getEntries()->deleteEntryType($this->pageEntryType);
        Craft::$app->getFields()->deleteField($this->matrixField);
        Craft::$app->getEntries()->deleteEntryType($this->blockEntryType);
        Craft::$app->getFields()->deleteField($this->contentBlockField);
        Craft::$app->getFields()->deleteField($this->blockTextField);
        parent::_after();
    }

    /**
     * Reproduces #19543: a batched lookup of a nested ContentBlock (nested inside a Matrix block entry)
     * whose owner is a revision misses the real, populated content block and silently substitutes an
     * empty one, because the batched query requires the content block's own row to be flagged as a
     * revision -- which it isn't, when the block was relinked to the revision rather than forked.
     */
    public function testBatchedLookupFindsContentBlockForRevisionOwner(): void
    {
        $primarySiteId = Craft::$app->getSites()->getPrimarySite()->id;

        // 1. Create a page with one Matrix block, its ContentBlock populated with real content.
        $page = new Entry();
        $page->sectionId = $this->section->id;
        $page->typeId = $this->pageEntryType->id;
        $page->siteId = $primarySiteId;
        $page->title = 'Test Page';
        $page->setFieldValue('testMatrix', [
            'new1' => [
                'type' => 'testMatrixBlock',
                'fields' => [
                    'testBlock' => ['fields' => ['blockText' => 'original content']],
                ],
            ],
        ]);
        if (!$this->elements->saveElement($page)) {
            throw new RuntimeException('Could not save page: ' . implode(', ', $page->getFirstErrors()));
        }

        $canonicalBlockEntry = Entry::find()
            ->ownerId($page->id)
            ->fieldId($this->matrixField->id)
            ->siteId($primarySiteId)
            ->status(null)
            ->one();
        self::assertNotNull($canonicalBlockEntry, 'Expected to find the canonical Matrix block entry.');

        /** @var ContentBlockElement $canonicalContentBlock */
        $canonicalContentBlock = $canonicalBlockEntry->getFieldValue('testBlock');
        self::assertSame('original content', $canonicalContentBlock->getFieldValue('blockText'));

        // 2. Create a revision of the page. `NestedElementManager::createRevisions()` gives the nested
        // Matrix block entry its own genuine revision row.
        $revisionId = Craft::$app->getRevisions()->createRevision($page);

        $revisionBlockEntry = Entry::find()
            ->ownerId($revisionId)
            ->fieldId($this->matrixField->id)
            ->siteId($primarySiteId)
            ->revisions(null)
            ->status(null)
            ->one();
        self::assertNotNull($revisionBlockEntry, 'Expected the revision to have its own nested Matrix block entry.');
        self::assertTrue($revisionBlockEntry->getIsRevision());

        // 3. Simulate the state the issue's author confirmed in production: the revision's nested
        // ContentBlock never got forked into its own revision row -- it's still the canonical row,
        // simply relinked (via `elements_owners`) to the revision-owned Matrix block entry. Do this by
        // hard-deleting whichever ContentBlock *did* get created for the revision block entry, and
        // relinking the canonical ContentBlock (whose own `revisionId` is null) in its place -- exactly
        // what `NestedElementManager` itself does when it relinks a nested element instead of forking it.
        $forkedContentBlock = ContentBlockElement::find()
            ->fieldId($this->contentBlockField->id)
            ->ownerId($revisionBlockEntry->id)
            ->siteId($primarySiteId)
            ->revisions(null)
            ->status(null)
            ->one();
        if ($forkedContentBlock) {
            $this->elements->deleteElementById($forkedContentBlock->id, hardDelete: true);
        }

        Db::delete(Table::ELEMENTS_OWNERS, [
            'elementId' => $canonicalContentBlock->id,
            'ownerId' => $revisionBlockEntry->id,
        ]);
        Db::insert(Table::ELEMENTS_OWNERS, [
            'elementId' => $canonicalContentBlock->id,
            'ownerId' => $revisionBlockEntry->id,
            'sortOrder' => 1,
        ]);

        self::assertNull($canonicalContentBlock->revisionId, 'Expected the relinked ContentBlock to NOT be flagged as a revision.');

        // 4. Reproduce a batched resave: query the canonical + revision Matrix block entries together in
        // a single call, so `ContentBlock::_normalizeValueInternal()` takes its batched-lookup code path
        // (populated by `ElementQuery::afterPopulate()` on any multi-result query -- exactly what a
        // `ResaveElements` job does when it processes a chunk of entries, revisions included).
        $batch = Entry::find()
            ->id([$canonicalBlockEntry->id, $revisionBlockEntry->id])
            ->siteId($primarySiteId)
            ->revisions(null)
            ->status(null)
            ->all();
        self::assertCount(2, $batch);

        $batchedRevisionBlockEntry = null;
        foreach ($batch as $e) {
            if ($e->id === $revisionBlockEntry->id) {
                $batchedRevisionBlockEntry = $e;
            }
        }
        self::assertNotNull($batchedRevisionBlockEntry);

        // This is where GH-19543 bites: the batched lookup's `->revisions(true)` misses the relinked
        // canonical row, and a brand new, empty, unsaved ContentBlockElement is silently substituted for
        // the real one.
        $contentBlock = $batchedRevisionBlockEntry->getFieldValue('testBlock');
        self::assertNotNull($contentBlock->id, 'Expected the batched ContentBlock lookup to find the existing, saved element, not create a new empty one.');
        self::assertSame('original content', $contentBlock->getFieldValue('blockText'));
    }
}
