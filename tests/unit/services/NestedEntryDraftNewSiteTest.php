<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\base\FieldInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\enums\PropagationMethod;
use craft\events\CancelableEvent;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Matrix;
use craft\fields\PlainText;
use craft\helpers\ElementHelper;
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
use yii\base\Event;

/**
 * Regression test for https://github.com/craftcms/cms/issues/19756
 *
 * Editing a nested entry twice in a draft that was enabled for a site its canonical entry isn’t saved in used to
 * cause an infinite loop, because `ElementHelper::belongsToCanonicalOwner()` looked for the owner’s canonical entry
 * in the draft’s site, and when it wasn’t there, `getCanonical()` returned the draft itself.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class NestedEntryDraftNewSiteTest extends TestCase
{
    /**
     * How many times the canonical entry can be looked up in the new site before we treat it as an infinite loop
     */
    private const MAX_CANONICAL_LOOKUPS = 25;

    protected Elements $elements;
    protected Drafts $drafts;
    private PlainText $textField;
    private EntryType $blockEntryType;
    private Matrix $matrixField;
    private EntryType $ownerEntryType;
    private Section $section;
    private int $canonicalLookups = 0;

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
        $this->blockEntryType->setFieldLayout($this->_makeFieldLayout($this->textField));
        if (!$entriesService->saveEntryType($this->blockEntryType)) {
            throw new RuntimeException('Could not save Matrix block entry type.');
        }

        // “Save entries to all sites the owner element is saved in”, in the Blocks view mode
        $this->matrixField = new Matrix();
        $this->matrixField->name = 'Test Matrix';
        $this->matrixField->handle = 'testMatrix';
        $this->matrixField->propagationMethod = PropagationMethod::All;
        $this->matrixField->viewMode = Matrix::VIEW_MODE_BLOCKS;
        $this->matrixField->setEntryTypes([$this->blockEntryType]);
        if (!$fieldsService->saveField($this->matrixField)) {
            throw new RuntimeException('Could not save Matrix field.');
        }

        $this->ownerEntryType = new EntryType();
        $this->ownerEntryType->name = 'Test Matrix Owner';
        $this->ownerEntryType->handle = 'testMatrixOwner';
        $this->ownerEntryType->setFieldLayout($this->_makeFieldLayout($this->matrixField));
        if (!$entriesService->saveEntryType($this->ownerEntryType)) {
            throw new RuntimeException('Could not save owner entry type.');
        }

        // “Let each entry choose which sites it should be saved to”
        $this->section = new Section();
        $this->section->name = 'Test Matrix Section';
        $this->section->handle = 'testMatrixSection';
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
        Event::off(EntryQuery::class, ElementQuery::EVENT_BEFORE_PREPARE, [$this, 'countCanonicalLookup']);
        Craft::$app->getEntries()->deleteSection($this->section);
        Craft::$app->getEntries()->deleteEntryType($this->ownerEntryType);
        Craft::$app->getFields()->deleteField($this->matrixField);
        Craft::$app->getEntries()->deleteEntryType($this->blockEntryType);
        Craft::$app->getFields()->deleteField($this->textField);
        parent::_after();
    }

    public function testBelongsToCanonicalOwnerWithDraftInNewSite(): void
    {
        [$entry, $draft] = $this->_createEntryWithDraftInNewSite();

        // A nested entry that's primarily owned by the draft, in the new site
        $nestedEntry = $this->_nestedEntry($draft);
        $nestedEntry->setPrimaryOwnerId($draft->id);

        $this->_watchForCanonicalLookups($entry);
        self::assertFalse(ElementHelper::belongsToCanonicalOwner($nestedEntry, $draft));

        // One that's primarily owned by the canonical entry should still belong to it
        $nestedEntry->setPrimaryOwnerId($entry->id);
        self::assertTrue(ElementHelper::belongsToCanonicalOwner($nestedEntry, $draft));
    }

    public function testEditingNestedEntryTwiceInDraftForNewSite(): void
    {
        [$entry, $draft] = $this->_createEntryWithDraftInNewSite();
        $this->_watchForCanonicalLookups($entry);

        // The first edit gives the draft its own copy of the nested entry
        $this->_editNestedEntry($draft, 'First edit');
        $draft = $this->_draftForNewSite($draft);
        $nestedEntry = $this->_nestedEntry($draft);
        self::assertSame('First edit', $nestedEntry->getFieldValue('blockText'));
        self::assertSame($draft->id, $nestedEntry->getPrimaryOwnerId());

        // The second edit used to loop forever
        $this->_editNestedEntry($draft, 'Second edit');
        $draft = $this->_draftForNewSite($draft);
        self::assertSame('Second edit', $this->_nestedEntry($draft)->getFieldValue('blockText'));

        // The canonical entry’s nested entry should be untouched
        $canonicalNestedEntry = $this->_nestedEntry(
            Entry::find()->id($entry->id)->siteId($this->_primarySiteId())->status(null)->one(),
        );
        self::assertSame('Original', $canonicalNestedEntry->getFieldValue('blockText'));
    }

    /**
     * Fails fast if the canonical entry is looked up in the new site (where it doesn’t exist) over and over,
     * rather than letting an infinite loop run until PHP runs out of memory.
     */
    public function countCanonicalLookup(CancelableEvent $event): void
    {
        /** @var EntryQuery $query */
        $query = $event->sender;
        if (
            $query->id === $this->_canonicalId &&
            $query->siteId === $this->_newSiteId() &&
            ++$this->canonicalLookups > self::MAX_CANONICAL_LOOKUPS
        ) {
            throw new RuntimeException(sprintf(
                'The canonical entry was looked up in the new site more than %s times. Infinite loop?',
                self::MAX_CANONICAL_LOOKUPS,
            ));
        }
    }

    private ?int $_canonicalId = null;

    private function _watchForCanonicalLookups(Entry $entry): void
    {
        $this->_canonicalId = $entry->id;
        $this->canonicalLookups = 0;
        Event::on(EntryQuery::class, ElementQuery::EVENT_BEFORE_PREPARE, [$this, 'countCanonicalLookup']);
    }

    /**
     * Creates an entry with one nested entry, saved in the primary site only, and a provisional draft of it that’s
     * been enabled for the new site as well.
     *
     * @return array{Entry,Entry} The canonical entry, and its draft in the new site
     */
    private function _createEntryWithDraftInNewSite(): array
    {
        $primarySiteId = $this->_primarySiteId();
        $newSiteId = $this->_newSiteId();

        $entry = new Entry();
        $entry->sectionId = $this->section->id;
        $entry->typeId = $this->ownerEntryType->id;
        $entry->siteId = $primarySiteId;
        $entry->title = 'Test Entry';
        $entry->setEnabledForSite([$primarySiteId => true]);
        $entry->setFieldValue('testMatrix', [
            'new1' => ['type' => 'testMatrixBlock', 'fields' => ['blockText' => 'Original']],
        ]);
        if (!$this->elements->saveElement($entry)) {
            throw new RuntimeException('Could not save entry: ' . implode(', ', $entry->getFirstErrors()));
        }
        self::assertNull(Entry::find()->id($entry->id)->siteId($newSiteId)->status(null)->one());

        /** @var Entry $draft */
        $draft = $this->drafts->createDraft($entry, 1, provisional: true);
        $draft->setEnabledForSite([
            $primarySiteId => true,
            $newSiteId => true,
        ]);
        if (!$this->elements->saveElement($draft)) {
            throw new RuntimeException('Could not save draft: ' . implode(', ', $draft->getFirstErrors()));
        }

        return [$entry, $this->_draftForNewSite($draft)];
    }

    /**
     * Edits the draft’s nested entry the way the Blocks view mode posts it, and saves the draft.
     */
    private function _editNestedEntry(Entry $draft, string $text): void
    {
        $nestedEntry = $this->_nestedEntry($draft);
        $draft->setFieldValue('testMatrix', [
            'sortOrder' => [$nestedEntry->id],
            'entries' => [
                $nestedEntry->id => ['type' => 'testMatrixBlock', 'fields' => ['blockText' => $text]],
            ],
        ]);
        if (!$this->elements->saveElement($draft)) {
            throw new RuntimeException('Could not save draft: ' . implode(', ', $draft->getFirstErrors()));
        }
    }

    private function _draftForNewSite(Entry $draft): Entry
    {
        $draftForNewSite = Entry::find()
            ->draftId($draft->draftId)
            ->provisionalDrafts()
            ->siteId($this->_newSiteId())
            ->status(null)
            ->one();
        self::assertNotNull($draftForNewSite, 'Expected the draft to have propagated to the new site.');
        return $draftForNewSite;
    }

    private function _nestedEntry(Entry $owner): Entry
    {
        $nestedEntries = $owner->getFieldValue('testMatrix')->status(null)->all();
        self::assertCount(1, $nestedEntries);
        return $nestedEntries[0];
    }

    private function _makeFieldLayout(FieldInterface ...$fields): FieldLayout
    {
        $fieldLayout = new FieldLayout(['type' => Entry::class]);
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
}
