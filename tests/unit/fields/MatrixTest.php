<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\fields;

use Craft;
use craft\elements\Entry;
use craft\events\DefineEntryTypesForFieldEvent;
use craft\fields\Matrix;
use craft\test\TestCase;
use crafttests\fixtures\EntryFixture;

/**
 * Unit tests for the Matrix custom field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class MatrixTest extends TestCase
{
    /**
     * @inheritdoc
     */
    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntryFixture::class,
            ],
        ];
    }

    /**
     * @dataProvider nestedElementManagerViewModeDataProvider
     * @param string $viewMode
     * @param bool $rendersEntries
     * @return void
     */
    public function testNestedElementManagerViewModeUsesDefinedEntryTypes(string $viewMode, bool $rendersEntries): void
    {
        $owner = new Entry([
            'id' => 999999,
            'sectionId' => 1013,
            'typeId' => 1013,
            'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
            'title' => 'Matrix owner',
        ]);

        $field = $owner->getFieldLayout()->getFieldByHandle('matrixCardsField');
        self::assertInstanceOf(Matrix::class, $field);
        $field->viewMode = $viewMode;

        $allowedEntryType = Craft::$app->getEntries()->getEntryTypeById(1016);
        $excludedEntryType = Craft::$app->getEntries()->getEntryTypeById(1017);
        self::assertNotNull($allowedEntryType);
        self::assertNotNull($excludedEntryType);
        $field->setEntryTypes([$allowedEntryType, $excludedEntryType]);

        $existingEntry = new Entry([
            'fieldId' => $field->id,
            'typeId' => $excludedEntryType->id,
            'siteId' => $owner->siteId,
            'title' => 'Existing excluded entry',
        ]);
        $existingEntry->setPrimaryOwner($owner);
        $existingEntry->setOwner($owner);

        $value = Entry::find();
        $value->setCachedResult([$existingEntry]);
        $owner->setFieldValue($field->handle, $value);

        $eventCount = 0;
        $eventHandler = function(DefineEntryTypesForFieldEvent $event) use (
            &$eventCount,
            $owner,
            $existingEntry,
            $allowedEntryType,
        ): void {
            $eventCount++;
            self::assertSame($owner, $event->element);
            self::assertSame([$existingEntry], $event->value);
            $event->entryTypes = [$allowedEntryType];
        };
        $field->on(Matrix::EVENT_DEFINE_ENTRY_TYPES, $eventHandler);

        $view = Craft::$app->getView();
        $view->startJsBuffer();
        try {
            $html = $field->getInputHtml($value, $owner);
            $js = $view->clearJsBuffer(false);
        } finally {
            $field->off(Matrix::EVENT_DEFINE_ENTRY_TYPES, $eventHandler);
        }

        self::assertSame(1, $eventCount);
        self::assertIsString($js);
        self::assertStringContainsString('"typeId":1016', $js);
        self::assertStringNotContainsString('"typeId":1017', $js);
        self::assertStringContainsString('const entryTypeIds = [1016];', $js);

        if ($rendersEntries) {
            self::assertStringContainsString('Existing excluded entry', $html);
        }
    }

    /**
     * @dataProvider nestedElementManagerViewModeDataProvider
     * @param string $viewMode
     * @return void
     */
    public function testNestedElementManagerViewModeUsesAllEntryTypesWithoutHandlers(string $viewMode): void
    {
        $owner = new Entry([
            'id' => 999999,
            'sectionId' => 1013,
            'typeId' => 1013,
            'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
            'title' => 'Matrix owner',
        ]);

        $field = $owner->getFieldLayout()->getFieldByHandle('matrixCardsField');
        self::assertInstanceOf(Matrix::class, $field);
        $field->viewMode = $viewMode;
        $field->setEntryTypes([1016, 1017]);

        $value = Entry::find();
        $value->setCachedResult([]);
        $owner->setFieldValue($field->handle, $value);

        $view = Craft::$app->getView();
        $view->startJsBuffer();
        $field->getInputHtml($value, $owner);
        $js = $view->clearJsBuffer(false);

        self::assertIsString($js);
        self::assertStringContainsString('"typeId":1016', $js);
        self::assertStringContainsString('"typeId":1017', $js);
        self::assertStringContainsString('const entryTypeIds = [1016,1017];', $js);
    }

    /**
     * @return array<string,array{string,bool}>
     */
    public static function nestedElementManagerViewModeDataProvider(): array
    {
        return [
            'cards' => [Matrix::VIEW_MODE_CARDS, true],
            'cards grid' => [Matrix::VIEW_MODE_CARDS_GRID, true],
            'index' => [Matrix::VIEW_MODE_INDEX, false],
        ];
    }
}
