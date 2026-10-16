<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Matrix;
use craft\fields\PlainText;
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
 * Reproduction test for https://github.com/craftcms/cms/issues/19594
 *
 * When a nested element (e.g. a Matrix block entry) is saved on its own — independently of its owner —
 * only that element’s own `dateUpdated` timestamp gets updated. None of its ancestors’ `dateUpdated`
 * timestamps change, even though their nested content did.
 *
 * `craft\services\Revisions::createRevision()` decides whether an element actually needs a new revision by
 * comparing its `dateUpdated` timestamp to the `dateCreated` timestamp of its most recent revision
 * (@see \craft\services\Revisions::createRevision()). Since a deeply-nested edit never touches the
 * ancestors’ `dateUpdated` values, that freshness check finds nothing changed at every ancestor level and
 * short-circuits — reusing the existing (stale) revisions instead of creating fresh ones that reflect the
 * nested change. Reverting to that “new” revision later then wipes out the nested edit.
 *
 * This test builds a two-levels-deep Matrix structure (Page > outer Matrix block > inner Matrix block),
 * establishes an initial revision, edits the innermost block directly (as if via its own edit page,
 * without resaving the Page), and confirms that a subsequently-created Page revision is a genuinely new
 * revision that captures the updated nested content — rather than reusing the stale prior revision.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class NestedElementOwnerDateUpdatedTest extends TestCase
{
    protected Elements $elements;
    private PlainText $blockTextField;
    private EntryType $innerBlockEntryType;
    private Matrix $innerMatrixField;
    private EntryType $outerBlockEntryType;
    private Matrix $outerMatrixField;
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

        // The entry type used as the *inner* Matrix block type: Page > outer Matrix > inner Matrix > this.
        $this->innerBlockEntryType = new EntryType();
        $this->innerBlockEntryType->name = 'Test Inner Block';
        $this->innerBlockEntryType->handle = 'testInnerBlock';
        $this->innerBlockEntryType->hasTitleField = false;
        $this->innerBlockEntryType->titleFormat = '{id}';
        $this->innerBlockEntryType->setFieldLayout(
            $this->_makeFieldLayout(Entry::class, $this->blockTextField)
        );
        if (!Craft::$app->getEntries()->saveEntryType($this->innerBlockEntryType)) {
            throw new RuntimeException('Could not save inner block entry type.');
        }

        $this->innerMatrixField = new Matrix();
        $this->innerMatrixField->name = 'Test Inner Matrix';
        $this->innerMatrixField->handle = 'testInnerMatrix';
        $this->innerMatrixField->setEntryTypes([$this->innerBlockEntryType]);
        if (!Craft::$app->getFields()->saveField($this->innerMatrixField)) {
            throw new RuntimeException('Could not save inner matrix field.');
        }

        // The entry type used as the *outer* Matrix block type: Page > outer Matrix > this.
        $this->outerBlockEntryType = new EntryType();
        $this->outerBlockEntryType->name = 'Test Outer Block';
        $this->outerBlockEntryType->handle = 'testOuterBlock';
        $this->outerBlockEntryType->hasTitleField = false;
        $this->outerBlockEntryType->titleFormat = '{id}';
        $this->outerBlockEntryType->setFieldLayout(
            $this->_makeFieldLayout(Entry::class, $this->innerMatrixField)
        );
        if (!Craft::$app->getEntries()->saveEntryType($this->outerBlockEntryType)) {
            throw new RuntimeException('Could not save outer block entry type.');
        }

        $this->outerMatrixField = new Matrix();
        $this->outerMatrixField->name = 'Test Outer Matrix';
        $this->outerMatrixField->handle = 'testOuterMatrix';
        $this->outerMatrixField->setEntryTypes([$this->outerBlockEntryType]);
        if (!Craft::$app->getFields()->saveField($this->outerMatrixField)) {
            throw new RuntimeException('Could not save outer matrix field.');
        }

        $this->pageEntryType = new EntryType();
        $this->pageEntryType->name = 'Test Page';
        $this->pageEntryType->handle = 'testPage';
        $this->pageEntryType->hasTitleField = true;
        $this->pageEntryType->setFieldLayout(
            $this->_makeFieldLayout(Entry::class, $this->outerMatrixField)
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
        Craft::$app->getFields()->deleteField($this->outerMatrixField);
        Craft::$app->getEntries()->deleteEntryType($this->outerBlockEntryType);
        Craft::$app->getFields()->deleteField($this->innerMatrixField);
        Craft::$app->getEntries()->deleteEntryType($this->innerBlockEntryType);
        Craft::$app->getFields()->deleteField($this->blockTextField);
        parent::_after();
    }

    /**
     * Reproduces #19594: saving a doubly-nested Matrix block on its own doesn’t bump its ancestors’
     * `dateUpdated` timestamps, so a subsequently-requested revision of the top-level owner gets reused
     * (stale) rather than freshly created — losing track of the nested change entirely.
     */
    public function testSavingNestedElementTouchesOwnersRecursively(): void
    {
        $primarySiteId = Craft::$app->getSites()->getPrimarySite()->id;

        // 1. Create a page with a nested (outer Matrix > inner Matrix > plain text) structure.
        $page = new Entry();
        $page->sectionId = $this->section->id;
        $page->typeId = $this->pageEntryType->id;
        $page->siteId = $primarySiteId;
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
        if (!$this->elements->saveElement($page)) {
            throw new RuntimeException('Could not save page: ' . implode(', ', $page->getFirstErrors()));
        }

        $outerBlockEntry = Entry::find()
            ->ownerId($page->id)
            ->fieldId($this->outerMatrixField->id)
            ->siteId($primarySiteId)
            ->status(null)
            ->one();
        self::assertNotNull($outerBlockEntry, 'Expected to find the canonical outer Matrix block entry.');

        $innerBlockEntry = Entry::find()
            ->ownerId($outerBlockEntry->id)
            ->fieldId($this->innerMatrixField->id)
            ->siteId($primarySiteId)
            ->status(null)
            ->one();
        self::assertNotNull($innerBlockEntry, 'Expected to find the canonical inner Matrix block entry.');
        self::assertSame('original content', $innerBlockEntry->getFieldValue('blockText'));

        // 2. Establish an initial revision for the page, which cascades down and creates matching
        // revisions for both nested block levels.
        $revisionId1 = Craft::$app->getRevisions()->createRevision($page);

        $originalPageDateUpdated = $page->dateUpdated;
        $originalOuterBlockDateUpdated = $outerBlockEntry->dateUpdated;

        // Make sure the upcoming edit gets a distinct (later) `dateUpdated` timestamp, since timestamps
        // are only stored with second precision.
        sleep(1);

        // 3. Edit the *innermost* block directly and save only that element — exactly as if it were
        // edited via its own nested-entry edit page, without resaving the Page or the outer block.
        $innerBlockEntry->setFieldValue('blockText', 'updated content');
        if (!$this->elements->saveElement($innerBlockEntry)) {
            throw new RuntimeException('Could not save inner block: ' . implode(', ', $innerBlockEntry->getFirstErrors()));
        }

        // 4. The fix: saving the canonical inner block should have bumped `dateUpdated` on both of its
        // ancestors, recursively, even though neither was directly touched.
        $refetchedOuterBlockEntry = Entry::find()
            ->id($outerBlockEntry->id)
            ->siteId($primarySiteId)
            ->status(null)
            ->one();
        self::assertNotNull($refetchedOuterBlockEntry);
        self::assertGreaterThan(
            $originalOuterBlockDateUpdated->getTimestamp(),
            $refetchedOuterBlockEntry->dateUpdated->getTimestamp(),
            'Expected the outer Matrix block’s dateUpdated to be bumped when its nested inner block was saved.',
        );

        $refetchedPage = Entry::find()
            ->id($page->id)
            ->siteId($primarySiteId)
            ->status(null)
            ->one();
        self::assertNotNull($refetchedPage);
        self::assertGreaterThan(
            $originalPageDateUpdated->getTimestamp(),
            $refetchedPage->dateUpdated->getTimestamp(),
            'Expected the page’s dateUpdated to be bumped (recursively) when its doubly-nested block was saved.',
        );

        // 5. Requesting a new revision for the page (using the freshly-refetched element, just like a
        // real, separate request would) should now create a *genuinely new* revision...
        $revisionId2 = Craft::$app->getRevisions()->createRevision($refetchedPage);
        self::assertNotSame($revisionId1, $revisionId2, 'Expected a new revision to be created, since nested content changed.');

        // 6. ...and that new revision’s nested content should reflect the update, all the way down.
        $revisionOuterBlockEntry = Entry::find()
            ->ownerId($revisionId2)
            ->fieldId($this->outerMatrixField->id)
            ->siteId($primarySiteId)
            ->revisions(null)
            ->status(null)
            ->one();
        self::assertNotNull($revisionOuterBlockEntry, 'Expected the new revision to have its own nested outer Matrix block entry.');

        $revisionInnerBlockEntry = Entry::find()
            ->ownerId($revisionOuterBlockEntry->id)
            ->fieldId($this->innerMatrixField->id)
            ->siteId($primarySiteId)
            ->revisions(null)
            ->status(null)
            ->one();
        self::assertNotNull($revisionInnerBlockEntry, 'Expected the new revision’s outer block to have its own nested inner Matrix block entry.');

        self::assertSame(
            'updated content',
            $revisionInnerBlockEntry->getFieldValue('blockText'),
            'Expected the new revision to capture the updated nested content, not the stale original.',
        );
    }
}
