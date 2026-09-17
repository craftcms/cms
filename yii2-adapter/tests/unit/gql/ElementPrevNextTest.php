<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql;

use Craft;
use craft\elements\Entry;
use craft\enums\PropagationMethod;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\GqlSchema;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\test\TestCase;
use CraftCms\Cms\Section\Enums\SectionType;
use RuntimeException;

/**
 * Tests that the `prev`/`next` GraphQL element-navigation fields stay scoped to what the
 * active schema is allowed to read, rather than rebuilding an unscoped query (see
 * craft\gql\types\elements\Element::resolve()).
 */
class ElementPrevNextTest extends TestCase
{
    private EntryType $entryType;
    private Section $sectionA;
    private Section $sectionB;
    private Entry $entryA1;
    private Entry $entryA2;

    protected function _before(): void
    {
        parent::_before();

        Craft::$app->getGql()->flushCaches();
        Craft::$app->getConfig()->getGeneral()->enableGraphqlCaching = false;

        $primarySiteId = Craft::$app->getSites()->getPrimarySite()->id;

        $this->entryType = new EntryType();
        $this->entryType->name = 'Prev/Next Test Type';
        $this->entryType->handle = 'prevNextTestType';
        $this->entryType->hasTitleField = true;
        $this->entryType->setFieldLayout($this->_makeFieldLayout());
        if (!Craft::$app->getEntries()->saveEntryType($this->entryType)) {
            throw new RuntimeException('Could not save entry type.');
        }

        $this->sectionA = $this->_makeSection('prevNextTestSectionA', $primarySiteId);
        $this->sectionB = $this->_makeSection('prevNextTestSectionB', $primarySiteId);

        // Saved in this order so the elements' IDs land in ascending order: A1, B1, A2.
        // Section B sits between two Section A entries so scoped navigation has to skip
        // over it, rather than just happening to stop before reaching it.
        $this->entryA1 = $this->_makeEntry($this->sectionA, 'Entry A1', $primarySiteId);
        $this->_makeEntry($this->sectionB, 'Entry B1', $primarySiteId);
        $this->entryA2 = $this->_makeEntry($this->sectionA, 'Entry A2', $primarySiteId);
    }

    protected function _after(): void
    {
        Craft::$app->getEntries()->deleteSection($this->sectionA);
        Craft::$app->getEntries()->deleteSection($this->sectionB);
        Craft::$app->getEntries()->deleteEntryType($this->entryType);

        parent::_after();
    }

    /**
     * `next(orderBy: "id asc")` must skip past an out-of-scope neighbor and return the next
     * element the active schema can actually read, instead of leaking the out-of-scope one.
     */
    public function testNextSkipsOutOfScopeElement(): void
    {
        // `orderBy` is (deliberately, for backwards compatibility - see cleanseQueryCriteria())
        // a no-op for `prev`/`next`, so the underlying query falls back to entries' default
        // order (newest first), making A2 the first row and A1 the last. "Next" from A2 is
        // therefore A1, skipping over the out-of-scope B1 that sits between them by ID.
        $result = $this->_queryNeighbor($this->entryA2->id, 'next');

        self::assertNotNull($result);
        self::assertEquals($this->entryA1->id, $result['id']);
        self::assertSame('prevNextTestSectionA', $result['sectionHandle']);
    }

    /**
     * `prev(orderBy: "id asc")` must behave symmetrically to `next`.
     */
    public function testPrevSkipsOutOfScopeElement(): void
    {
        $result = $this->_queryNeighbor($this->entryA1->id, 'prev');

        self::assertNotNull($result);
        self::assertEquals($this->entryA2->id, $result['id']);
        self::assertSame('prevNextTestSectionA', $result['sectionHandle']);
    }

    /**
     * If nothing beyond the out-of-scope neighbor is in scope either, `next` must return
     * null rather than falling back to an unscoped result.
     */
    public function testNextReturnsNullWhenNoInScopeElementRemains(): void
    {
        Craft::$app->getElements()->deleteElement($this->entryA2, true);

        $result = $this->_queryNeighbor($this->entryA1->id, 'next');

        self::assertNull($result);
    }

    /**
     * With no criteria, `prev`/`next` read the cached sibling from the query's own result
     * array (see craft\base\Element::_getRelativeElement()) rather than routing through the
     * new scoped-query path, so this is untouched by this fix.
     */
    public function testNoArgumentsUsesResultSetCache(): void
    {
        $schema = new GqlSchema([
            'name' => 'Prev/Next Test Schema',
            'scope' => ["sections.{$this->sectionA->uid}:read"],
        ]);

        Craft::$app->getGql()->setActiveSchema($schema);

        $query = '{ entries(section: "prevNextTestSectionA") { id next { id } prev { id } } }';
        $response = Craft::$app->getGql()->executeQuery($schema, $query);

        self::assertArrayNotHasKey('errors', $response, json_encode($response['errors'] ?? []));

        $entries = $response['data']['entries'];
        self::assertCount(2, $entries);

        // Default order is newest first, so A2 is first and A1 is last in this result set.
        self::assertEquals($this->entryA2->id, $entries[0]['id']);
        self::assertNull($entries[0]['prev']);
        self::assertEquals($this->entryA1->id, $entries[0]['next']['id']);

        self::assertEquals($this->entryA1->id, $entries[1]['id']);
        self::assertEquals($this->entryA2->id, $entries[1]['prev']['id']);
        self::assertNull($entries[1]['next']);
    }

    /**
     * Runs `entries(id: $anchorId) { $field(orderBy: "id asc") { id title sectionHandle } }`
     * against a schema scoped to Section A only, and returns the `$field` value.
     *
     * @return array{id: mixed, title: string, sectionHandle: string}|null
     */
    private function _queryNeighbor(int $anchorId, string $field): ?array
    {
        $schema = new GqlSchema([
            'name' => 'Prev/Next Test Schema',
            'scope' => ["sections.{$this->sectionA->uid}:read"],
        ]);

        Craft::$app->getGql()->setActiveSchema($schema);

        $query = sprintf(
            '{ entries(id: %d) { %s(orderBy: "id asc") { id title sectionHandle } } }',
            $anchorId,
            $field,
        );

        $response = Craft::$app->getGql()->executeQuery($schema, $query);

        self::assertArrayNotHasKey('errors', $response, json_encode($response['errors'] ?? []));

        return $response['data']['entries'][0][$field] ?? null;
    }

    private function _makeSection(string $handle, int $siteId): Section
    {
        $section = new Section();
        $section->name = $handle;
        $section->handle = $handle;
        $section->type = SectionType::Channel;
        $section->enableVersioning = false;
        $section->propagationMethod = PropagationMethod::All;
        $section->setEntryTypes([$this->entryType]);
        $section->setSiteSettings([
            new Section_SiteSettings([
                'siteId' => $siteId,
                'enabledByDefault' => true,
                'hasUrls' => false,
            ]),
        ]);

        if (!Craft::$app->getEntries()->saveSection($section)) {
            throw new RuntimeException("Could not save section \"$handle\".");
        }

        return $section;
    }

    private function _makeEntry(Section $section, string $title, int $siteId): Entry
    {
        $entry = new Entry();
        $entry->sectionId = $section->id;
        $entry->typeId = $this->entryType->id;
        $entry->title = $title;
        $entry->siteId = $siteId;

        if (!Craft::$app->getElements()->saveElement($entry)) {
            throw new RuntimeException("Could not save entry \"$title\".");
        }

        return $entry;
    }

    private function _makeFieldLayout(): FieldLayout
    {
        $fieldLayout = new FieldLayout(['type' => Entry::class]);
        $tab = new FieldLayoutTab(['name' => 'Content']);
        $tab->setLayout($fieldLayout);
        $tab->setElements([new EntryTitleField()]);
        $fieldLayout->setTabs([$tab]);

        return $fieldLayout;
    }
}
