<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements;

use Craft;
use craft\base\FieldInterface;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Entries;
use craft\fields\Lightswitch;
use craft\fields\PlainText;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\test\TestCase;
use RuntimeException;

/**
 * Regression test for https://github.com/craftcms/cms/issues/19737
 *
 * Changing an entry’s type used to drop the values of content-column fields that both entry types share,
 * because content is keyed by field layout element UID, and the carried-over values weren’t written under
 * the new entry type’s keys.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class EntryTypeChangeContentTest extends TestCase
{
    private Lightswitch $lightswitchField;
    private PlainText $textField;
    private Entries $entriesField;
    private EntryType $typeA;
    private EntryType $typeB;
    private Section $section;
    private int $relatedId;

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();
        $fieldsService = Craft::$app->getFields();
        $entriesService = Craft::$app->getEntries();

        $this->lightswitchField = new Lightswitch();
        $this->lightswitchField->name = 'Test Lightswitch';
        $this->lightswitchField->handle = 'testLightswitch';

        $this->textField = new PlainText();
        $this->textField->name = 'Test Text';
        $this->textField->handle = 'testText';

        $this->entriesField = new Entries();
        $this->entriesField->name = 'Test Entries';
        $this->entriesField->handle = 'testEntries';

        foreach ([$this->lightswitchField, $this->textField, $this->entriesField] as $field) {
            if (!$fieldsService->saveField($field)) {
                throw new RuntimeException("Could not save the $field->handle field.");
            }
        }

        $this->typeA = $this->_createEntryType('testTypeChangeA');
        $this->typeB = $this->_createEntryType('testTypeChangeB');

        $this->section = new Section();
        $this->section->name = 'Test Type Change';
        $this->section->handle = 'testTypeChange';
        $this->section->type = Section::TYPE_CHANNEL;
        $this->section->setEntryTypes([$this->typeA, $this->typeB]);
        $this->section->setSiteSettings([
            new Section_SiteSettings([
                'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
                'enabledByDefault' => true,
                'hasUrls' => false,
            ]),
        ]);
        if (!$entriesService->saveSection($this->section)) {
            throw new RuntimeException('Could not save the section.');
        }
    }

    /**
     * @inheritdoc
     */
    protected function _after(): void
    {
        $entriesService = Craft::$app->getEntries();
        $fieldsService = Craft::$app->getFields();
        $entriesService->deleteSection($this->section);
        $entriesService->deleteEntryType($this->typeA);
        $entriesService->deleteEntryType($this->typeB);
        $fieldsService->deleteField($this->lightswitchField);
        $fieldsService->deleteField($this->textField);
        $fieldsService->deleteField($this->entriesField);
        parent::_after();
    }

    /**
     * Mirrors the element editor, which renders the form (loading the field values) before the new type is applied.
     */
    public function testSharedFieldValuesSurviveTypeChangeAfterValuesLoaded(): void
    {
        $entry = $this->_changeType(true);
        $this->_assertValuesKept($entry);
    }

    public function testSharedFieldValuesSurviveTypeChange(): void
    {
        $entry = $this->_changeType(false);
        $this->_assertValuesKept($entry);
    }

    private function _changeType(bool $loadValues): Entry
    {
        $related = new Entry([
            'sectionId' => $this->section->id,
            'typeId' => $this->typeA->id,
            'title' => 'Related',
        ]);
        self::assertTrue(Craft::$app->getElements()->saveElement($related));
        $this->relatedId = $related->id;

        $entry = new Entry([
            'sectionId' => $this->section->id,
            'typeId' => $this->typeA->id,
            'title' => 'Test Entry',
        ]);
        $entry->setFieldValue('testLightswitch', true);
        $entry->setFieldValue('testText', 'Hello');
        $entry->setFieldValue('testEntries', [$related->id]);
        self::assertTrue(Craft::$app->getElements()->saveElement($entry));

        $entry = $this->_fetch($entry->id);
        if ($loadValues) {
            $entry->getFieldValues();
        }
        // Change the type the way the element editor does
        $entry->setAttributesFromRequest(['typeId' => $this->typeB->id]);
        self::assertTrue(Craft::$app->getElements()->saveElement($entry), implode(', ', $entry->getFirstErrors()));

        return $this->_fetch($entry->id);
    }

    private function _assertValuesKept(Entry $entry): void
    {
        self::assertSame($this->typeB->id, $entry->typeId);
        self::assertSame([$this->relatedId], $entry->getFieldValue('testEntries')->ids(), 'Relation field value lost');
        self::assertTrue($entry->getFieldValue('testLightswitch'), 'Lightswitch value lost');
        self::assertSame('Hello', $entry->getFieldValue('testText'), 'Plain Text value lost');

        // A subsequent save that only changes one field shouldn't prune the others
        $entry->setFieldValue('testText', 'Edited');
        self::assertTrue(Craft::$app->getElements()->saveElement($entry));
        $entry = $this->_fetch($entry->id);
        self::assertTrue($entry->getFieldValue('testLightswitch'), 'Lightswitch value lost after a subsequent save');
        self::assertSame('Edited', $entry->getFieldValue('testText'));
    }

    private function _fetch(int $id): Entry
    {
        $entry = Entry::find()->id($id)->status(null)->one();
        self::assertNotNull($entry);
        return $entry;
    }

    private function _createEntryType(string $handle): EntryType
    {
        $entryType = new EntryType();
        $entryType->name = $handle;
        $entryType->handle = $handle;
        $entryType->setFieldLayout($this->_makeFieldLayout(
            $this->lightswitchField,
            $this->textField,
            $this->entriesField,
        ));
        if (!Craft::$app->getEntries()->saveEntryType($entryType)) {
            throw new RuntimeException("Could not save the $handle entry type.");
        }
        return $entryType;
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
}
