<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\base\FieldInterface;
use craft\db\Table;
use craft\elements\Entry;
use craft\enums\PropagationMethod;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fields\Entries;
use craft\fields\Matrix;
use craft\fields\PlainText;
use craft\helpers\Db;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\services\Drafts;
use craft\services\Elements;
use craft\test\TestCase;
use crafttests\fixtures\SitesFixture;
use DateTime;
use RuntimeException;

/**
 * Reproduction test for https://github.com/craftcms/cms/issues/20151
 *
 * When a draft adds a nested entry to a versioned Matrix field on a multi-site install, applying the draft
 * duplicates the nested entry into the canonical owner via `Elements::duplicateElement()`. Its site clones
 * used to lose their `propagating` flag to `$newAttributes`, so the first one saved for another site created
 * the nested entry’s first revision before its own nested entries were copied over. That empty revision
 * was then reused by the owner’s revision, and reverting to it deleted the nested content.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class NestedEntryDraftMultiSiteRevisionTest extends TestCase
{
    protected Elements $elements;
    protected Drafts $drafts;
    private PlainText $bodyField;
    private EntryType $targetEntryType;
    private Section $targetSection;
    private Entries $relatedField;
    private EntryType $childEntryType;
    private Matrix $innerMatrixField;
    private EntryType $blockEntryType;
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
        $this->drafts = Craft::$app->getDrafts();
        $fieldsService = Craft::$app->getFields();
        $entriesService = Craft::$app->getEntries();

        $this->bodyField = new PlainText();
        $this->bodyField->name = 'Body';
        $this->bodyField->handle = 'body';
        if (!$fieldsService->saveField($this->bodyField)) {
            throw new RuntimeException('Could not save body field.');
        }

        $this->targetEntryType = new EntryType();
        $this->targetEntryType->name = 'Target';
        $this->targetEntryType->handle = 'target';
        $this->targetEntryType->setFieldLayout($this->_makeFieldLayout());
        if (!$entriesService->saveEntryType($this->targetEntryType)) {
            throw new RuntimeException('Could not save target entry type.');
        }

        $this->targetSection = $this->_makeSection('Targets', 'targets', $this->targetEntryType);

        // Relations are localized, so each site can relate different entries
        $this->relatedField = new Entries([
            'name' => 'Related',
            'handle' => 'related',
            'sources' => '*',
            'localizeRelations' => true,
        ]);
        if (!$fieldsService->saveField($this->relatedField)) {
            throw new RuntimeException('Could not save related field.');
        }

        $this->childEntryType = new EntryType();
        $this->childEntryType->name = 'Child';
        $this->childEntryType->handle = 'child';
        $this->childEntryType->setFieldLayout($this->_makeFieldLayout($this->bodyField));
        if (!$entriesService->saveEntryType($this->childEntryType)) {
            throw new RuntimeException('Could not save child entry type.');
        }

        $this->innerMatrixField = new Matrix();
        $this->innerMatrixField->name = 'Inner Blocks';
        $this->innerMatrixField->handle = 'innerBlocks';
        $this->innerMatrixField->propagationMethod = PropagationMethod::All;
        $this->innerMatrixField->viewMode = Matrix::VIEW_MODE_BLOCKS;
        $this->innerMatrixField->setEntryTypes([$this->childEntryType]);
        if (!$fieldsService->saveField($this->innerMatrixField)) {
            throw new RuntimeException('Could not save inner Matrix field.');
        }

        $this->blockEntryType = new EntryType();
        $this->blockEntryType->name = 'Block';
        $this->blockEntryType->handle = 'block';
        $this->blockEntryType->setFieldLayout($this->_makeFieldLayout($this->innerMatrixField, $this->relatedField));
        if (!$entriesService->saveEntryType($this->blockEntryType)) {
            throw new RuntimeException('Could not save block entry type.');
        }

        $this->outerMatrixField = new Matrix();
        $this->outerMatrixField->name = 'Blocks';
        $this->outerMatrixField->handle = 'blocks';
        $this->outerMatrixField->propagationMethod = PropagationMethod::All;
        $this->outerMatrixField->viewMode = Matrix::VIEW_MODE_CARDS;
        $this->outerMatrixField->enableVersioning = true;
        $this->outerMatrixField->setEntryTypes([$this->blockEntryType]);
        if (!$fieldsService->saveField($this->outerMatrixField)) {
            throw new RuntimeException('Could not save outer Matrix field.');
        }

        $this->pageEntryType = new EntryType();
        $this->pageEntryType->name = 'Page';
        $this->pageEntryType->handle = 'page';
        $this->pageEntryType->setFieldLayout($this->_makeFieldLayout($this->outerMatrixField));
        if (!$entriesService->saveEntryType($this->pageEntryType)) {
            throw new RuntimeException('Could not save page entry type.');
        }

        $this->section = $this->_makeSection('Pages', 'pages', $this->pageEntryType);
    }

    /**
     * @inheritdoc
     */
    protected function _after(): void
    {
        Craft::$app->getEntries()->deleteSection($this->section);
        Craft::$app->getEntries()->deleteEntryType($this->pageEntryType);
        Craft::$app->getFields()->deleteField($this->outerMatrixField);
        Craft::$app->getEntries()->deleteEntryType($this->blockEntryType);
        Craft::$app->getFields()->deleteField($this->innerMatrixField);
        Craft::$app->getEntries()->deleteEntryType($this->childEntryType);
        Craft::$app->getFields()->deleteField($this->bodyField);
        Craft::$app->getFields()->deleteField($this->relatedField);
        Craft::$app->getEntries()->deleteSection($this->targetSection);
        Craft::$app->getEntries()->deleteEntryType($this->targetEntryType);
        parent::_after();
    }

    public function testBlockAddedThroughDraftGetsCompleteRevision(): void
    {
        $primarySiteId = $this->_primarySiteId();

        // 1. Create a page with one block, which has a child entry
        $page = new Entry();
        $page->sectionId = $this->section->id;
        $page->typeId = $this->pageEntryType->id;
        $page->siteId = $primarySiteId;
        $page->title = 'Test Page';
        $page->setFieldValue('blocks', [
            'new1' => $this->_blockData('Block A', 'Child A'),
        ]);
        if (!$this->elements->saveElement($page)) {
            throw new RuntimeException('Could not save page: ' . implode(', ', $page->getFirstErrors()));
        }
        $this->_backdateRevisions($page->id);
        $blockA = $this->_blocks($page)[0];

        // 2. Add a second block with a child entry through a draft, and apply it
        /** @var Entry $draft */
        $draft = $this->drafts->createDraft($page, 1, 'Draft 1');
        $draft->setFieldValue('blocks', [
            'sortOrder' => [$blockA->id, 'new1'],
            'entries' => [
                $blockA->id => ['type' => 'block'],
                'new1' => $this->_blockData('Block B', 'Child B'),
            ],
        ]);
        if (!$this->elements->saveElement($draft)) {
            throw new RuntimeException('Could not save draft: ' . implode(', ', $draft->getFirstErrors()));
        }
        $page = $this->drafts->applyDraft($draft);

        $blocks = $this->_blocks($page);
        self::assertCount(2, $blocks);
        $blockB = $blocks[1];
        self::assertSame('Block B', $blockB->title);
        self::assertSame(['Child B'], $this->_childTitles($blockB));

        // 3. Block B’s first revision should include its child entry
        $blockBRevisions = Entry::find()
            ->revisionOf($blockB)
            ->siteId($primarySiteId)
            ->status(null)
            ->orderBy(['elements.id' => SORT_ASC])
            ->all();
        self::assertNotEmpty($blockBRevisions);
        self::assertSame(['Child B'], $this->_childTitles($blockBRevisions[0]));

        // 4. So should Block B within the page’s latest revision
        $pageRevision = Entry::find()
            ->revisionOf($page)
            ->siteId($primarySiteId)
            ->status(null)
            ->orderBy(['elements.id' => SORT_DESC])
            ->one();
        self::assertNotNull($pageRevision);
        $revisionBlocks = $this->_blocks($pageRevision);
        self::assertCount(2, $revisionBlocks);
        self::assertSame(['Child B'], $this->_childTitles($revisionBlocks[1]));

        // 5. Reverting to that revision should keep Block B’s child entry
        /** @var Entry $page */
        $page = Craft::$app->getRevisions()->revertToRevision($pageRevision, 1);
        $blocks = $this->_blocks($page);
        self::assertCount(2, $blocks);
        self::assertSame(['Child B'], $this->_childTitles($blocks[1]));
    }

    public function testBlockAddedThroughDraftKeepsLocalizedRelationsPerSite(): void
    {
        $primarySiteId = $this->_primarySiteId();
        $otherSiteId = $this->_otherSiteId();
        $targetA = $this->_saveEntry($this->targetSection, $this->targetEntryType, 'Target A');
        $targetB = $this->_saveEntry($this->targetSection, $this->targetEntryType, 'Target B');

        $page = $this->_saveEntry($this->section, $this->pageEntryType, 'Test Page');
        $this->_backdateRevisions($page->id);

        // 1. Add a block through a draft, relating Target A
        /** @var Entry $draft */
        $draft = $this->drafts->createDraft($page, 1, 'Draft 1');
        $blockData = $this->_blockData('Block B', 'Child B');
        $blockData['fields']['related'] = [$targetA->id];
        $draft->setFieldValue('blocks', [
            'sortOrder' => ['new1'],
            'entries' => ['new1' => $blockData],
        ]);
        if (!$this->elements->saveElement($draft)) {
            throw new RuntimeException('Could not save draft: ' . implode(', ', $draft->getFirstErrors()));
        }

        // 2. Relate Target B to the draft’s block in the other site instead
        $draftBlock = $this->_blocks($draft, $otherSiteId)[0];
        $draftBlock->setFieldValue('related', [$targetB->id]);
        if (!$this->elements->saveElement($draftBlock)) {
            throw new RuntimeException('Could not save draft block: ' . implode(', ', $draftBlock->getFirstErrors()));
        }

        // 3. Apply the draft. Each site should keep its own relation.
        $page = $this->drafts->applyDraft($draft);
        $blockB = $this->_blocks($page)[0];
        self::assertSame([$targetA->id], $this->_relatedIds($blockB->id, $primarySiteId));
        self::assertSame([$targetB->id], $this->_relatedIds($blockB->id, $otherSiteId));

        // 4. So should the block’s first revision
        $blockRevisionId = Entry::find()
            ->revisionOf($blockB)
            ->siteId($primarySiteId)
            ->status(null)
            ->orderBy(['elements.id' => SORT_ASC])
            ->select(['elements.id'])
            ->scalar();
        self::assertNotFalse($blockRevisionId);
        self::assertSame([$targetA->id], $this->_relatedIds((int)$blockRevisionId, $primarySiteId));
        self::assertSame([$targetB->id], $this->_relatedIds((int)$blockRevisionId, $otherSiteId));
    }

    /**
     * Returns the posted data for a new block with a single child entry.
     */
    private function _blockData(string $title, string $childTitle): array
    {
        return [
            'type' => 'block',
            'title' => $title,
            'fields' => [
                'innerBlocks' => [
                    'new1' => [
                        'type' => 'child',
                        'title' => $childTitle,
                        'fields' => ['body' => "$childTitle body"],
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns the IDs of the entries related to an element in a given site.
     *
     * @return int[]
     */
    private function _relatedIds(int $elementId, int $siteId): array
    {
        /** @var Entry $element */
        $element = Entry::find()
            ->id($elementId)
            ->siteId($siteId)
            ->revisions(null)
            ->status(null)
            ->one();
        self::assertNotNull($element);
        return $element->getFieldValue('related')->status(null)->ids();
    }

    private function _saveEntry(Section $section, EntryType $entryType, string $title): Entry
    {
        $entry = new Entry();
        $entry->sectionId = $section->id;
        $entry->typeId = $entryType->id;
        $entry->siteId = $this->_primarySiteId();
        $entry->title = $title;
        if (!$this->elements->saveElement($entry)) {
            throw new RuntimeException("Could not save entry “{$title}”: " . implode(', ', $entry->getFirstErrors()));
        }
        return $entry;
    }

    /**
     * Saves a versioned channel section that propagates entries to both sites.
     */
    private function _makeSection(string $name, string $handle, EntryType $entryType): Section
    {
        $section = new Section();
        $section->name = $name;
        $section->handle = $handle;
        $section->type = Section::TYPE_CHANNEL;
        $section->enableVersioning = true;
        $section->propagationMethod = PropagationMethod::All;
        $section->setEntryTypes([$entryType]);
        $section->setSiteSettings(array_map(fn(int $siteId) => new Section_SiteSettings([
            'siteId' => $siteId,
            'enabledByDefault' => true,
            'hasUrls' => false,
        ]), [$this->_primarySiteId(), $this->_otherSiteId()]));
        if (!Craft::$app->getEntries()->saveSection($section)) {
            throw new RuntimeException("Could not save section “{$name}”.");
        }
        return $section;
    }

    /**
     * Moves the element’s existing revisions back in time, so the next save gets a new revision
     * rather than reusing the latest one created within the same second.
     */
    private function _backdateRevisions(int $canonicalId): void
    {
        $revisionIds = Entry::find()
            ->revisionOf($canonicalId)
            ->site('*')
            ->status(null)
            ->ids();
        Db::update(Table::ELEMENTS, [
            'dateCreated' => Db::prepareDateForDb(new DateTime('-1 minute')),
        ], ['id' => $revisionIds], updateTimestamp: false);
    }

    /**
     * @return Entry[]
     */
    private function _blocks(Entry $page, ?int $siteId = null): array
    {
        return Entry::find()
            ->ownerId($page->id)
            ->fieldId($this->outerMatrixField->id)
            ->siteId($siteId ?? $this->_primarySiteId())
            ->revisions($page->getIsRevision() ? null : false)
            ->status(null)
            ->all();
    }

    /**
     * @return string[]
     */
    private function _childTitles(Entry $block): array
    {
        $children = Entry::find()
            ->ownerId($block->id)
            ->fieldId($this->innerMatrixField->id)
            ->siteId($this->_primarySiteId())
            ->revisions($block->getIsRevision() ? null : false)
            ->status(null)
            ->all();
        return array_map(fn(Entry $child) => $child->title, $children);
    }

    private function _makeFieldLayout(FieldInterface ...$fields): FieldLayout
    {
        $fieldLayout = new FieldLayout(['type' => Entry::class]);
        $tab = new FieldLayoutTab(['name' => 'Content']);
        $tab->setLayout($fieldLayout);
        $tab->setElements([
            new EntryTitleField(),
            ...array_map(fn(FieldInterface $field) => new CustomField($field), $fields),
        ]);
        $fieldLayout->setTabs([$tab]);
        return $fieldLayout;
    }

    private function _primarySiteId(): int
    {
        return (int)Craft::$app->getSites()->getPrimarySite()->id;
    }

    private function _otherSiteId(): int
    {
        return (int)Craft::$app->getSites()->getSiteByHandle('testSite1')->id;
    }
}
