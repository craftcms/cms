<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\base\FieldInterface;
use craft\elements\ContentBlock as ContentBlockElement;
use craft\elements\Entry;
use craft\enums\PropagationMethod;
use craft\fieldlayoutelements\CustomField;
use craft\fields\ContentBlock;
use craft\fields\Matrix;
use craft\fields\PlainText;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\services\Drafts;
use craft\services\Elements;
use craft\test\TestCase;
use crafttests\fixtures\SitesFixture;
use RuntimeException;

/**
 * Regression test for https://github.com/craftcms/cms/issues/18281
 *
 * Adding a new site to a draft whose owner entry has a `craft\fields\ContentBlock` field containing a
 * non-propagating Matrix field used to throw "Attempting to duplicate an element in an unsupported site."
 * The content block (which always propagates to all of its owner's sites) is still shared with the
 * canonical entry, so when it was re-fetched in the new site without its owner context, it fell back to
 * its primary (canonical) owner, which doesn't exist in that site, and duplicating its Matrix entries
 * into the site failed.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class ContentBlockDraftNewSiteTest extends TestCase
{
    protected Elements $elements;
    protected Drafts $drafts;
    private PlainText $textField;
    private EntryType $blockEntryType;
    private Matrix $matrixField;
    private ContentBlock $contentBlockField;
    private EntryType $ownerEntryType;
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
        $this->drafts = Craft::$app->getDrafts();
        $fieldsService = Craft::$app->getFields();
        $entriesService = Craft::$app->getEntries();

        $this->textField = new PlainText();
        $this->textField->name = 'Block Text';
        $this->textField->handle = 'blockText';
        if (!$fieldsService->saveField($this->textField)) {
            throw new RuntimeException('Could not save text field.');
        }

        $this->blockEntryType = new EntryType();
        $this->blockEntryType->name = 'Test Matrix Block';
        $this->blockEntryType->handle = 'testMatrixBlock';
        $this->blockEntryType->hasTitleField = false;
        $this->blockEntryType->titleFormat = '{id}';
        $this->blockEntryType->setFieldLayout($this->_makeFieldLayout(Entry::class, $this->textField));
        if (!$entriesService->saveEntryType($this->blockEntryType)) {
            throw new RuntimeException('Could not save Matrix block entry type.');
        }

        $this->matrixField = new Matrix();
        $this->matrixField->name = 'Test Matrix';
        $this->matrixField->handle = 'testMatrix';
        $this->matrixField->propagationMethod = PropagationMethod::None;
        $this->matrixField->setEntryTypes([$this->blockEntryType]);
        if (!$fieldsService->saveField($this->matrixField)) {
            throw new RuntimeException('Could not save Matrix field.');
        }

        $this->contentBlockField = new ContentBlock();
        $this->contentBlockField->name = 'Test Block';
        $this->contentBlockField->handle = 'testBlock';
        $this->contentBlockField->setFieldLayout(
            $this->_makeFieldLayout(ContentBlockElement::class, $this->textField, $this->matrixField)
        );
        if (!$fieldsService->saveField($this->contentBlockField)) {
            throw new RuntimeException('Could not save content block field.');
        }

        $this->ownerEntryType = new EntryType();
        $this->ownerEntryType->name = 'Test Block Entry Type';
        $this->ownerEntryType->handle = 'testBlockEntryType';
        $this->ownerEntryType->setFieldLayout($this->_makeFieldLayout(Entry::class, $this->contentBlockField));
        if (!$entriesService->saveEntryType($this->ownerEntryType)) {
            throw new RuntimeException('Could not save owner entry type.');
        }

        // Custom propagation, so a draft can be enabled for a site its canonical entry isn't in
        $this->section = new Section();
        $this->section->name = 'Test Block Section';
        $this->section->handle = 'testBlockSection';
        $this->section->type = Section::TYPE_CHANNEL;
        $this->section->propagationMethod = PropagationMethod::Custom;
        $this->section->setEntryTypes([$this->ownerEntryType]);
        $this->section->setSiteSettings(array_map(fn(int $siteId) => new Section_SiteSettings([
            'siteId' => $siteId,
            'enabledByDefault' => true,
            'hasUrls' => false,
        ]), [$this->_primarySiteId(), $this->_newSiteId()]));
        if (!$entriesService->saveSection($this->section)) {
            throw new RuntimeException('Could not save section.');
        }
    }

    /**
     * @inheritdoc
     */
    protected function _after(): void
    {
        Craft::$app->getEntries()->deleteSection($this->section);
        Craft::$app->getEntries()->deleteEntryType($this->ownerEntryType);
        Craft::$app->getFields()->deleteField($this->contentBlockField);
        Craft::$app->getFields()->deleteField($this->matrixField);
        Craft::$app->getEntries()->deleteEntryType($this->blockEntryType);
        Craft::$app->getFields()->deleteField($this->textField);
        parent::_after();
    }

    /**
     * Builds a single-tab field layout containing the given custom fields.
     *
     * @param class-string $type
     */
    private function _makeFieldLayout(string $type, FieldInterface ...$fields): FieldLayout
    {
        $fieldLayout = new FieldLayout(['type' => $type]);
        $tab = new FieldLayoutTab(['name' => 'Content']);
        $tab->setLayout($fieldLayout);
        $tab->setElements(array_map(fn(FieldInterface $field) => new CustomField($field), $fields));
        $fieldLayout->setTabs([$tab]);
        return $fieldLayout;
    }

    private function _primarySiteId(): int
    {
        return (int)Craft::$app->getSites()->getPrimarySite()->id;
    }

    private function _newSiteId(): int
    {
        return (int)Craft::$app->getSites()->getSiteByHandle('testSite1')->id;
    }

    public function testAddingSiteToDraftDuplicatesContentBlockMatrixEntries(): void
    {
        $primarySiteId = $this->_primarySiteId();
        $newSiteId = $this->_newSiteId();

        // 1. Create the canonical entry in the primary site only, with a Matrix entry in its content block.
        $entry = new Entry();
        $entry->sectionId = $this->section->id;
        $entry->typeId = $this->ownerEntryType->id;
        $entry->siteId = $primarySiteId;
        $entry->title = 'Test Entry';
        $entry->setFieldValue('testBlock', [
            'fields' => [
                'blockText' => 'Content block',
                'testMatrix' => [
                    'new1' => ['type' => 'testMatrixBlock', 'fields' => ['blockText' => 'Nested']],
                ],
            ],
        ]);
        if (!$this->elements->saveElement($entry)) {
            throw new RuntimeException('Could not save entry: ' . implode(', ', $entry->getFirstErrors()));
        }

        /** @var ContentBlockElement $contentBlock */
        $contentBlock = $entry->getFieldValue('testBlock');
        $nestedEntry = $contentBlock->getFieldValue('testMatrix')->status(null)->one();
        self::assertNotNull($nestedEntry);

        // 2. Create a draft, and enable it for the new site without touching its content block.
        /** @var Entry $draft */
        $draft = $this->drafts->createDraft($entry, 1, 'Test Draft');
        $draft->setEnabledForSite([
            $primarySiteId => true,
            $newSiteId => true,
        ]);
        if (!$this->elements->saveElement($draft)) {
            throw new RuntimeException('Could not save draft: ' . implode(', ', $draft->getFirstErrors()));
        }

        // 3. The draft's (still shared) content block should now have its own copy of the Matrix entry in the new site.
        $draftForNewSite = Entry::find()->draftId($draft->draftId)->siteId($newSiteId)->status(null)->one();
        self::assertNotNull($draftForNewSite, 'Expected the draft to have propagated to the new site.');

        /** @var ContentBlockElement $contentBlockForNewSite */
        $contentBlockForNewSite = $draftForNewSite->getFieldValue('testBlock');
        self::assertSame($contentBlock->id, $contentBlockForNewSite->id);

        $nestedEntriesForNewSite = $contentBlockForNewSite->getFieldValue('testMatrix')->status(null)->all();
        self::assertCount(1, $nestedEntriesForNewSite);
        self::assertSame('Nested', $nestedEntriesForNewSite[0]->getFieldValue('blockText'));
        self::assertNotSame($nestedEntry->id, $nestedEntriesForNewSite[0]->id);
    }
}
