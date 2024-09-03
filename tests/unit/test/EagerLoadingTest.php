<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\test;

use Codeception\Test\Unit;
use Craft;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\test\TestCase;
use crafttests\fixtures\EntryWithMatrixFixture;
use yii\base\ErrorException;

/**
 * Unit tests for eager loading content
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.10
 */
class EagerLoadingTest extends TestCase
{
    public function _fixtures(): array
    {
        return [
            'entry-with-matrix' => [
                'class' => EntryWithMatrixFixture::class,
            ],
        ];
    }

    /**
     * Test eager loading simple relational field, matrix field,
     * relational field within a matrix field
     *
     * @return void
     */
    public function testEagerLoadingScenario1(): void
    {
        // getting the entry
        $entry = Entry::find()
            ->title('Matrix with relational field')
            ->with([
                'relatedEntry', // field exists and is part of the layout
                'matrixSecond', // field exists and is part of the layout
                'matrixSecond.matrixLayout3:entriesSubfield', // field exists and is part of the layout
                'matrixFirst', // field exists and is NOT part of the layout
            ])
            ->one();

        self::assertNotNull($entry);

        // check if simple relational field e.g. related entry was eager loaded
        self::assertNotEmpty($entry->getEagerLoadedElements('relatedEntry'));

        // check if matrix field was eager loaded
        $matrixSecond = $entry->getEagerLoadedElements('matrixSecond');
        self::assertNotEmpty($matrixSecond);

        // check if relational field inside a matrix field was eager loaded
        self::assertNotEmpty($matrixSecond[0]->getEagerLoadedElements('entriesSubfield'));

        // check if eager loading field that's not part of the layout returns empty result
        self::assertEmpty($matrixSecond[1]->getEagerLoadedElements('entriesSubfield'));
        self::assertEmpty($entry->getEagerLoadedElements('matrixFirst'));
    }

    /**
     * Test if eager loading element attributes (e.g. revisions, descendants)
     * returns results (#12646, #12645)
     *
     * @return void
     */
    public function testEagerLoadingScenario2(): void
    {
        $entry = Entry::find()->sectionId(1004)->with(['revisions'])->one();
        self::assertNotEmpty($entry->getEagerLoadedElements('revisions'));
    }

    /**
     * Test eager loading relational field that doesn't exist,
     * exists, has content and is part of the layout, exists and is not part of the layout
     *
     * @return void
     */
    public function testEagerLoadingScenario3(): void
    {
        // get entries from section 1000
        // that section has field-layout: field-layout-1002----------------uid
        // which doesn't contain the 'relatedEntry' field created for section 1006
        $entries = Entry::find()->sectionId(1000)->limit(2)->all();

        // try to eager load a field that exists,
        // and is part of the layout for $entries that we retrieved
        try {
            Craft::$app->getElements()->eagerLoadElements(
                Entry::class,
                $entries,
                'matrixFirst' // field exists but is not part of the layout
            );
        } catch (ErrorException) {
            $this->fail();
        }
        self::assertTrue(true);

        // try to eager load a field that doesn't exist
        try {
            Craft::$app->getElements()->eagerLoadElements(
                Entry::class,
                $entries,
                'fieldDoesntExist' // field exists but is not part of the layout
            );
        } catch (ErrorException) {
            $this->fail();
        }
        self::assertTrue(true);

        // try to eager load a field that exists,
        // but is not part of the layout for $entries that we retrieved
        // this would throw a \base\yii\ErrorException on 4.3.8.1;
        // see https://github.com/craftcms/cms/issues/12648 for more info
        try {
            Craft::$app->getElements()->eagerLoadElements(
                Entry::class,
                $entries,
                'relatedEntry' // field exists but is not part of the layout
            );
        } catch (ErrorException) {
            $this->fail();
        }
        self::assertTrue(true);
    }

    /**
     * Test if removing a relational field, that was populated with content, from the layout
     * returns empty results
     *
     * @return void
     * @throws \yii\base\Exception
     */
    public function testEagerLoadingScenario4(): void
    {
        $this->_removeFieldFromLayout('field-layout-1003----------------uid', 'relatedEntry');

        $entry = Entry::find()
            ->title('Matrix with relational field')
            ->with([
                'relatedEntry', // field exists, was part of the layout and was populated with data, then was removed from the layout
            ])
            ->one();

        self::assertNotNull($entry);
        self::assertEmpty($entry->getEagerLoadedElements('relatedEntry'));
    }

    /**
     * Remove field by handle from layout by UUID
     *
     * @param string $layoutUid
     * @param string $fieldHandle
     * @return void
     * @throws \yii\base\Exception
     */
    private function _removeFieldFromLayout(string $layoutUid, string $fieldHandle): void
    {
        $fieldsService = Craft::$app->getFields();
        $fieldLayout = $fieldsService->getLayoutByUid($layoutUid);
        $tabs = $fieldLayout->getTabs();
        $layoutElements = $tabs[0]->getElements();
        foreach ($layoutElements as $key => $element) {
            if ($element instanceof CustomField) {
                if ($element->getField()->handle === $fieldHandle) {
                    unset($layoutElements[$key]);
                }
            }
        }

        $tabs[0]->setElements($layoutElements);
        $fieldsService->saveLayout($fieldLayout);
    }
}
