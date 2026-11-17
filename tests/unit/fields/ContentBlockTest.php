<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\fields;

use Craft;
use craft\elements\ContentBlock as ContentBlockElement;
use craft\elements\db\ElementQuery;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fields\ContentBlock;
use craft\fields\PlainText;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\test\TestCase;
use RuntimeException;
use yii\base\Event;

/**
 * Unit tests for the Content Block field
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class ContentBlockTest extends TestCase
{
    private PlainText $textField;
    private ContentBlock $contentBlockField;
    private EntryType $entryType;
    private Section $section;

    protected function _before(): void
    {
        parent::_before();
        $fieldsService = Craft::$app->getFields();
        $entriesService = Craft::$app->getEntries();

        $this->textField = new PlainText(['name' => 'Block Text', 'handle' => 'blockText']);
        if (!$fieldsService->saveField($this->textField)) {
            throw new RuntimeException('Could not save text field.');
        }

        $this->contentBlockField = new ContentBlock(['name' => 'Test Block', 'handle' => 'testBlock']);
        $this->contentBlockField->setFieldLayout($this->_makeFieldLayout(ContentBlockElement::class, new CustomField($this->textField)));
        if (!$fieldsService->saveField($this->contentBlockField)) {
            throw new RuntimeException('Could not save content block field.');
        }

        $this->entryType = new EntryType(['name' => 'Test Block Entry Type', 'handle' => 'testBlockEntryType']);
        $this->entryType->setFieldLayout($this->_makeFieldLayout(Entry::class, new CustomField($this->contentBlockField)));
        if (!$entriesService->saveEntryType($this->entryType)) {
            throw new RuntimeException('Could not save entry type.');
        }

        $this->section = new Section([
            'name' => 'Test Block Section',
            'handle' => 'testBlockSection',
            'type' => Section::TYPE_CHANNEL,
        ]);
        $this->section->setEntryTypes([$this->entryType]);
        $this->section->setSiteSettings([
            new Section_SiteSettings([
                'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
                'hasUrls' => false,
            ]),
        ]);
        if (!$entriesService->saveSection($this->section)) {
            throw new RuntimeException('Could not save section.');
        }
    }

    protected function _after(): void
    {
        Craft::$app->getEntries()->deleteSection($this->section);
        Craft::$app->getEntries()->deleteEntryType($this->entryType);
        Craft::$app->getFields()->deleteField($this->contentBlockField);
        Craft::$app->getFields()->deleteField($this->textField);
        parent::_after();
    }

    private function _makeFieldLayout(string $type, CustomField $element): FieldLayout
    {
        $fieldLayout = new FieldLayout(['type' => $type]);
        $tab = new FieldLayoutTab(['name' => 'Content', 'layout' => $fieldLayout]);
        $tab->setElements([$element]);
        $fieldLayout->setTabs([$tab]);
        return $fieldLayout;
    }

    /**
     * Content blocks that are lazy-loaded for a query result shouldn't query for their owners.
     *
     * @see https://github.com/craftcms/cms/issues/20776
     */
    public function testLazyLoadedContentBlocksDontQueryOwners(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $entry = new Entry([
                'sectionId' => $this->section->id,
                'typeId' => $this->entryType->id,
                'title' => "Entry $i",
            ]);
            $entry->setFieldValue('testBlock', ['fields' => ['blockText' => "Text $i"]]);
            if (!Craft::$app->getElements()->saveElement($entry)) {
                throw new RuntimeException('Could not save entry: ' . implode(', ', $entry->getFirstErrors()));
            }
        }

        $entries = Entry::find()->sectionId($this->section->id)->orderBy(['elements.id' => SORT_ASC])->all();
        self::assertCount(3, $entries);

        $entryQueries = 0;
        $handler = function() use (&$entryQueries) {
            $entryQueries++;
        };
        Event::on(EntryQuery::class, ElementQuery::EVENT_BEFORE_PREPARE, $handler);

        try {
            foreach ($entries as $i => $entry) {
                /** @var ContentBlockElement $contentBlock */
                $contentBlock = $entry->getFieldValue('testBlock');
                self::assertSame('Text ' . ($i + 1), $contentBlock->getFieldValue('blockText'));
                self::assertSame($entry, $contentBlock->getOwner());
                self::assertSame($entry, $contentBlock->getPrimaryOwner());
            }
        } finally {
            Event::off(EntryQuery::class, ElementQuery::EVENT_BEFORE_PREPARE, $handler);
        }

        self::assertSame(0, $entryQueries);
    }
}
