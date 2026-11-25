<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Entry;
use craft\elements\User;
use craft\enums\PropagationMethod;
use craft\events\ModelEvent;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
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
use yii\base\Event;

/**
 * Covers the element save performance optimizations, ensuring they don’t change the outcome of element saves:
 *
 * - Nested element ownership rows are upserted rather than deleted and reinserted.
 * - Element types are memoized by ID for the duration of an element save.
 * - Search keywords are inserted in batches.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class ElementSaveOptimizationsTest extends TestCase
{
    protected Elements $elements;
    private PlainText $textField;
    private EntryType $blockEntryType;
    private Matrix $matrixField;
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

        $this->textField = new PlainText();
        $this->textField->name = 'Optimization Text';
        $this->textField->handle = 'optimizationText';
        $this->textField->searchable = true;
        if (!Craft::$app->getFields()->saveField($this->textField)) {
            throw new RuntimeException('Could not save text field.');
        }

        $this->blockEntryType = new EntryType();
        $this->blockEntryType->name = 'Optimization Block';
        $this->blockEntryType->handle = 'optimizationBlock';
        $this->blockEntryType->hasTitleField = false;
        $this->blockEntryType->titleFormat = '{id}';
        $this->blockEntryType->setFieldLayout($this->_makeFieldLayout([$this->textField]));
        if (!Craft::$app->getEntries()->saveEntryType($this->blockEntryType)) {
            throw new RuntimeException('Could not save block entry type.');
        }

        $this->matrixField = new Matrix();
        $this->matrixField->name = 'Optimization Matrix';
        $this->matrixField->handle = 'optimizationMatrix';
        $this->matrixField->propagationMethod = PropagationMethod::All;
        $this->matrixField->searchable = true;
        $this->matrixField->setEntryTypes([$this->blockEntryType]);
        if (!Craft::$app->getFields()->saveField($this->matrixField)) {
            throw new RuntimeException('Could not save matrix field.');
        }

        $this->ownerEntryType = new EntryType();
        $this->ownerEntryType->name = 'Optimization Owner';
        $this->ownerEntryType->handle = 'optimizationOwner';
        $this->ownerEntryType->setFieldLayout($this->_makeFieldLayout([$this->textField, $this->matrixField], [new EntryTitleField()]));
        if (!Craft::$app->getEntries()->saveEntryType($this->ownerEntryType)) {
            throw new RuntimeException('Could not save owner entry type.');
        }

        // A section that propagates entries across all of the sites
        $this->section = new Section();
        $this->section->name = 'Optimization Section';
        $this->section->handle = 'optimizationSection';
        $this->section->type = Section::TYPE_CHANNEL;
        $this->section->enableVersioning = false;
        $this->section->propagationMethod = PropagationMethod::All;
        $this->section->setEntryTypes([$this->ownerEntryType]);
        $this->section->setSiteSettings(array_map(fn($site) => new Section_SiteSettings([
            'siteId' => $site->id,
            'enabledByDefault' => true,
            'hasUrls' => false,
        ]), Craft::$app->getSites()->getAllSites()));
        if (!Craft::$app->getEntries()->saveSection($this->section)) {
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
        Craft::$app->getEntries()->deleteEntryType($this->blockEntryType);
        Craft::$app->getFields()->deleteField($this->matrixField);
        Craft::$app->getFields()->deleteField($this->textField);
        parent::_after();
    }

    // Ownership
    // -------------------------------------------------------------------------

    public function testNewNestedEntriesGetOwnershipRows(): void
    {
        $owner = $this->_createOwner(['First', 'Second', 'Third']);
        $nested = $this->_nestedEntries($owner);

        self::assertSame(['First', 'Second', 'Third'], $this->_texts($nested));
        self::assertSame([
            [$nested[0]->id, 1],
            [$nested[1]->id, 2],
            [$nested[2]->id, 3],
        ], $this->_ownershipRows($owner));
    }

    public function testReorderingNestedEntriesUpdatesOwnershipRows(): void
    {
        $owner = $this->_createOwner(['First', 'Second', 'Third']);
        $nested = $this->_nestedEntries($owner);

        // Reverse the order, and modify each nested entry so they all get resaved
        $owner = $this->_reloadOwner($owner);
        $owner->setFieldValue('optimizationMatrix', $this->_editedMatrixValue(array_reverse($nested)));
        self::assertTrue($this->elements->saveElement($owner));

        // Still exactly one ownership row per nested entry, with the new sort orders
        self::assertSame([
            [$nested[2]->id, 1],
            [$nested[1]->id, 2],
            [$nested[0]->id, 3],
        ], $this->_ownershipRows($owner));

        // And the content changes propagated to every site
        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            self::assertSame(
                ['Third (edited)', 'Second (edited)', 'First (edited)'],
                $this->_texts($this->_nestedEntries($owner, $siteId)),
                "Unexpected nested content for site $siteId",
            );
        }
    }

    public function testResavingNestedEntryDirectlyKeepsOwnershipRow(): void
    {
        $owner = $this->_createOwner(['First', 'Second']);
        $nested = $this->_nestedEntries($owner);

        $second = $nested[1];
        $second->setFieldValue('optimizationText', 'Second (edited)');
        self::assertTrue($this->elements->saveElement($second));

        self::assertSame([
            [$nested[0]->id, 1],
            [$nested[1]->id, 2],
        ], $this->_ownershipRows($owner));
        self::assertSame(['First', 'Second (edited)'], $this->_texts($this->_nestedEntries($owner)));
    }

    public function testResavingNestedEntryChangesItsSortOrder(): void
    {
        $owner = $this->_createOwner(['First', 'Second']);
        $nested = $this->_nestedEntries($owner);

        $first = $nested[0];
        $first->setSortOrder(5);
        self::assertTrue($this->elements->saveElement($first));

        self::assertSame([
            [$nested[1]->id, 2],
            [$nested[0]->id, 5],
        ], $this->_ownershipRows($owner));
    }

    public function testResavingNestedEntryRestoresMissingOwnershipRow(): void
    {
        $owner = $this->_createOwner(['First', 'Second']);
        $nested = $this->_nestedEntries($owner);

        // Lose the ownership row for the first nested entry
        Db::delete(Table::ELEMENTS_OWNERS, [
            'elementId' => $nested[0]->id,
            'ownerId' => $owner->id,
        ]);
        self::assertSame([[$nested[1]->id, 2]], $this->_ownershipRows($owner));

        // Resaving it should put the row back
        $first = $nested[0];
        $first->setOwner($owner);
        $first->setSortOrder(1);
        self::assertTrue($this->elements->saveElement($first));

        self::assertSame([
            [$nested[0]->id, 1],
            [$nested[1]->id, 2],
        ], $this->_ownershipRows($owner));
    }

    public function testSavingNestedEntryForAnotherOwnerAddsOwnershipRow(): void
    {
        $owner = $this->_createOwner(['First']);
        $otherOwner = $this->_createOwner([]);
        $nested = $this->_nestedEntries($owner)[0];

        // Save the nested entry for another (non-primary) owner
        $nested->setOwner($otherOwner);
        $nested->setSortOrder(7);
        self::assertTrue($this->elements->saveElement($nested));

        self::assertSame([[$nested->id, 7]], $this->_ownershipRows($otherOwner));
        // The primary owner’s ownership row is left alone
        self::assertSame([[$nested->id, 1]], $this->_ownershipRows($owner));

        // Saving it again for the other owner just updates its sort order
        $nested->setSortOrder(3);
        self::assertTrue($this->elements->saveElement($nested));
        self::assertSame([[$nested->id, 3]], $this->_ownershipRows($otherOwner));
        self::assertSame([[$nested->id, 1]], $this->_ownershipRows($owner));
    }

    public function testResavingNestedEntryIssuesSingleOwnershipStatement(): void
    {
        $owner = $this->_createOwner(['First']);
        $nested = $this->_nestedEntries($owner)[0];
        $nested->setFieldValue('optimizationText', 'First (edited)');

        $statements = $this->_recordStatements(fn() => $this->elements->saveElement($nested));
        $ownershipStatements = array_values(array_filter(
            $statements,
            fn(string $sql) => str_contains($sql, Craft::$app->getDb()->getSchema()->getRawTableName(Table::ELEMENTS_OWNERS)) &&
                preg_match('/^\s*(INSERT|DELETE|UPDATE)\b/i', $sql),
        ));

        self::assertCount(1, $ownershipStatements, implode("\n", $ownershipStatements));
        self::assertMatchesRegularExpression('/^\s*INSERT\b/i', $ownershipStatements[0]);
    }

    // Element type memoization
    // -------------------------------------------------------------------------

    public function testElementTypeLookupsAreMemoizedDuringSave(): void
    {
        $owner = $this->_createOwner(['First']);
        $owner = $this->_reloadOwner($owner);
        $owner->title = 'Changed';

        $types = [];
        $statements = [];
        $handler = function(ModelEvent $event) use ($owner, &$types, &$statements) {
            if ($event->sender !== $owner) {
                return;
            }
            $statements = $this->_recordStatements(function() use ($owner, &$types) {
                for ($i = 0; $i < 3; $i++) {
                    $types[] = $this->elements->getElementTypeById($owner->id);
                }
            });
        };

        Event::on(Entry::class, Element::EVENT_AFTER_SAVE, $handler);
        try {
            self::assertTrue($this->elements->saveElement($owner));
        } finally {
            Event::off(Entry::class, Element::EVENT_AFTER_SAVE, $handler);
        }

        self::assertSame([Entry::class, Entry::class, Entry::class], $types);
        self::assertLessThanOrEqual(1, count($this->_elementTypeQueries($statements)));
    }

    public function testElementTypeLookupsAreNotMemoizedOutsideSaves(): void
    {
        $owner = $this->_createOwner([]);

        self::assertSame(Entry::class, $this->elements->getElementTypeById($owner->id));

        // Change the type directly; it should be picked up right away
        $this->_setElementType($owner->id, User::class);
        try {
            self::assertSame(User::class, $this->elements->getElementTypeById($owner->id));
        } finally {
            $this->_setElementType($owner->id, Entry::class);
        }

        $statements = $this->_recordStatements(function() use ($owner) {
            $this->elements->getElementTypeById($owner->id);
            $this->elements->getElementTypeById($owner->id);
        });
        self::assertCount(2, $this->_elementTypeQueries($statements));
    }

    public function testElementTypeMemoIsClearedAfterSave(): void
    {
        $owner = $this->_createOwner(['First']);
        $otherOwner = $this->_createOwner([]);

        // Memoize the other owner’s type during a save
        $handler = function(ModelEvent $event) use ($owner, $otherOwner) {
            if ($event->sender->id === $owner->id) {
                $this->elements->getElementTypeById($otherOwner->id);
            }
        };
        Event::on(Entry::class, Element::EVENT_AFTER_SAVE, $handler);
        try {
            self::assertTrue($this->elements->saveElement($this->_reloadOwner($owner)));
        } finally {
            Event::off(Entry::class, Element::EVENT_AFTER_SAVE, $handler);
        }

        // Once the save is complete, the memoized type shouldn't be used anymore
        $this->_setElementType($otherOwner->id, User::class);
        try {
            self::assertSame(User::class, $this->elements->getElementTypeById($otherOwner->id));
        } finally {
            $this->_setElementType($otherOwner->id, Entry::class);
        }
    }

    public function testElementTypeMemoIsClearedWhenSaveFails(): void
    {
        $owner = $this->_createOwner([]);
        $otherOwner = $this->_createOwner([]);

        $handler = function(ModelEvent $event) use ($otherOwner) {
            $this->elements->getElementTypeById($otherOwner->id);
            throw new RuntimeException('Save failed');
        };
        Event::on(Entry::class, Element::EVENT_BEFORE_SAVE, $handler);
        try {
            $this->elements->saveElement($this->_reloadOwner($owner));
            self::fail('The save should have thrown an exception.');
        } catch (RuntimeException $e) {
            self::assertSame('Save failed', $e->getMessage());
        } finally {
            Event::off(Entry::class, Element::EVENT_BEFORE_SAVE, $handler);
        }

        $this->_setElementType($otherOwner->id, User::class);
        try {
            self::assertSame(User::class, $this->elements->getElementTypeById($otherOwner->id));
        } finally {
            $this->_setElementType($otherOwner->id, Entry::class);
        }

        // Subsequent saves still work
        self::assertTrue($this->elements->saveElement($this->_reloadOwner($owner)));
    }

    public function testElementTypeMemoIsClearedWhenElementIsHardDeleted(): void
    {
        $owner = $this->_createOwner([]);
        $doomed = $this->_createOwner([]);

        $owner = $this->_reloadOwner($owner);
        $before = null;
        $after = 'not checked';
        $handler = function(ModelEvent $event) use ($owner, $doomed, &$before, &$after) {
            if ($event->sender !== $owner) {
                return;
            }
            $before = $this->elements->getElementTypeById($doomed->id);
            $this->elements->deleteElement($doomed, true);
            $after = $this->elements->getElementTypeById($doomed->id);
        };
        Event::on(Entry::class, Element::EVENT_AFTER_SAVE, $handler);
        try {
            self::assertTrue($this->elements->saveElement($owner));
        } finally {
            Event::off(Entry::class, Element::EVENT_AFTER_SAVE, $handler);
        }

        self::assertSame(Entry::class, $before);
        self::assertNull($after);
    }

    public function testMultiSiteNestedSaveLooksUpOwnerTypeOnce(): void
    {
        $owner = $this->_createOwner(['First', 'Second', 'Third']);

        $owner = $this->_reloadOwner($owner);
        $owner->setFieldValue('optimizationMatrix', $this->_editedMatrixValue($this->_nestedEntries($owner)));

        $statements = $this->_recordStatements(fn() => self::assertTrue($this->elements->saveElement($owner)));

        // 3 nested entries × 4 sites used to look up the owner’s type separately
        $typeQueries = $this->_elementTypeQueries($statements);
        self::assertLessThanOrEqual(1, count($typeQueries), implode("\n", $typeQueries));

        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            self::assertSame(
                ['First (edited)', 'Second (edited)', 'Third (edited)'],
                $this->_texts($this->_nestedEntries($owner, $siteId)),
            );
        }
    }

    // Search indexing
    // -------------------------------------------------------------------------

    public function testSavingIndexesKeywordsInEverySite(): void
    {
        $owner = $this->_createOwner(['Alpha block', 'Beta block'], 'Gamma owner');
        $nested = $this->_nestedEntries($owner);

        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            $ownerRows = $this->_searchIndexRows($owner->id, $siteId);
            self::assertSame(' gamma owner ', $ownerRows['title'] ?? null, "Owner title keywords missing for site $siteId");
            self::assertSame(' owner text ', $ownerRows["field:{$this->textField->id}"] ?? null);
            self::assertStringContainsString('alpha block', $ownerRows["field:{$this->matrixField->id}"] ?? '');
            self::assertStringContainsString('beta block', $ownerRows["field:{$this->matrixField->id}"] ?? '');

            $nestedRows = $this->_searchIndexRows($nested[0]->id, $siteId);
            self::assertSame(' alpha block ', $nestedRows["field:{$this->textField->id}"] ?? null, "Nested keywords missing for site $siteId");
        }
    }

    public function testIndexingOnlySomeFieldsLeavesOtherFieldKeywordsAlone(): void
    {
        $owner = $this->_createOwner(['Alpha block'], 'Gamma owner');
        $siteId = $owner->siteId;
        $matrixKey = "field:{$this->matrixField->id}";
        $textKey = "field:{$this->textField->id}";
        $originalMatrixKeywords = $this->_searchIndexRows($owner->id, $siteId)[$matrixKey];

        // Change the text field's value in memory, and only reindex that field
        $owner = $this->_reloadOwner($owner);
        $owner->title = 'Delta owner';
        $owner->setFieldValue('optimizationText', 'New text');
        Craft::$app->getSearch()->indexElementAttributes($owner, ['optimizationText']);

        $rows = $this->_searchIndexRows($owner->id, $siteId);
        self::assertSame(' new text ', $rows[$textKey]);
        self::assertSame(' delta owner ', $rows['title']);
        self::assertSame($originalMatrixKeywords, $rows[$matrixKey]);
    }

    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param string[] $blockTexts
     */
    private function _createOwner(array $blockTexts, string $title = 'Optimization Owner Entry'): Entry
    {
        $owner = new Entry();
        $owner->sectionId = $this->section->id;
        $owner->typeId = $this->ownerEntryType->id;
        $owner->title = $title;
        $owner->setFieldValue('optimizationText', 'Owner text');

        $blocks = [];
        foreach ($blockTexts as $i => $text) {
            $blocks['new' . ($i + 1)] = [
                'type' => $this->blockEntryType->handle,
                'fields' => ['optimizationText' => $text],
            ];
        }
        $owner->setFieldValue('optimizationMatrix', $blocks);

        if (!$this->elements->saveElement($owner)) {
            throw new RuntimeException('Could not save owner entry: ' . implode(', ', $owner->getFirstErrors()));
        }

        return $owner;
    }

    /**
     * Returns a Matrix field value (in the format posted by the control panel) that appends “ (edited)”
     * to each of the given nested entries’ text, in the given order.
     *
     * @param Entry[] $entries
     */
    private function _editedMatrixValue(array $entries): array
    {
        $value = ['sortOrder' => [], 'entries' => []];
        foreach ($entries as $entry) {
            $value['sortOrder'][] = $entry->id;
            $value['entries'][$entry->id] = [
                'type' => $this->blockEntryType->handle,
                'fields' => ['optimizationText' => $entry->getFieldValue('optimizationText') . ' (edited)'],
            ];
        }
        return $value;
    }

    private function _reloadOwner(Entry $owner): Entry
    {
        $reloaded = Entry::find()->id($owner->id)->siteId($owner->siteId)->status(null)->one();
        self::assertNotNull($reloaded);
        return $reloaded;
    }

    /**
     * @return Entry[]
     */
    private function _nestedEntries(Entry $owner, ?int $siteId = null): array
    {
        return Entry::find()
            ->ownerId($owner->id)
            ->siteId($siteId ?? $owner->siteId)
            ->status(null)
            ->orderBy(['elements_owners.sortOrder' => SORT_ASC])
            ->all();
    }

    /**
     * @param Entry[] $entries
     * @return string[]
     */
    private function _texts(array $entries): array
    {
        return array_map(fn(Entry $entry) => $entry->getFieldValue('optimizationText'), $entries);
    }

    /**
     * @return array<array{int,int}> [elementId, sortOrder] pairs, ordered by sort order
     */
    private function _ownershipRows(Entry $owner): array
    {
        $rows = (new Query())
            ->select(['elementId', 'sortOrder'])
            ->from(Table::ELEMENTS_OWNERS)
            ->where(['ownerId' => $owner->id])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();

        return array_map(fn(array $row) => [(int)$row['elementId'], (int)$row['sortOrder']], $rows);
    }

    /**
     * @return array<string,string> Keywords indexed by attribute name, or `field:<id>` for custom fields
     */
    private function _searchIndexRows(int $elementId, int $siteId): array
    {
        $rows = (new Query())
            ->select(['attribute', 'fieldId', 'keywords'])
            ->from(Table::SEARCHINDEX)
            ->where(['elementId' => $elementId, 'siteId' => $siteId])
            ->all();

        $keywords = [];
        foreach ($rows as $row) {
            $key = $row['attribute'] === 'field' ? "field:{$row['fieldId']}" : $row['attribute'];
            $keywords[$key] = $row['keywords'];
        }
        return $keywords;
    }

    private function _setElementType(int $elementId, string $type): void
    {
        Db::update(Table::ELEMENTS, ['type' => $type], ['id' => $elementId], updateTimestamp: false);
    }

    /**
     * Records the SQL of every statement executed or queried within the callback.
     *
     * @return string[]
     */
    private function _recordStatements(callable $callback): array
    {
        $db = Craft::$app->getDb();
        $oldCommandClass = $db->commandClass;
        $db->commandClass = RecordingCommand::class;
        RecordingCommand::startRecording();

        try {
            $callback();
        } finally {
            $db->commandClass = $oldCommandClass;
        }

        return RecordingCommand::stopRecordingStatements();
    }

    /**
     * @param string[] $statements
     * @return string[]
     */
    private function _elementTypeQueries(array $statements): array
    {
        $table = Craft::$app->getDb()->getSchema()->getRawTableName(Table::ELEMENTS);
        return array_values(array_filter(
            $statements,
            fn(string $sql) => (bool)preg_match(sprintf('/^\s*SELECT\s+[`"]type[`"]\s+FROM\s+[`"]%s[`"]/i', preg_quote($table, '/')), $sql),
        ));
    }

    /**
     * @param \craft\base\FieldInterface[] $fields
     * @param \craft\base\FieldLayoutElement[] $leadingElements
     */
    private function _makeFieldLayout(array $fields, array $leadingElements = []): FieldLayout
    {
        $fieldLayout = new FieldLayout(['type' => Entry::class]);
        $tab = new FieldLayoutTab(['name' => 'Content']);
        $tab->setLayout($fieldLayout);
        $tab->setElements([
            ...$leadingElements,
            ...array_map(fn($field) => new CustomField($field), $fields),
        ]);
        $fieldLayout->setTabs([$tab]);
        return $fieldLayout;
    }
}
