<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\behaviors;

use Codeception\Test\Unit;
use Craft;
use craft\base\FieldInterface;
use craft\behaviors\CustomFieldBehavior;
use craft\fields\PlainText;
use InvalidArgumentException;
use UnitTester;

/**
 * Unit tests for CustomFieldBehavior
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.4.0
 */
class CustomFieldBehaviorTest extends Unit
{
    /**
     * @var UnitTester
     */
    public $tester;

    /**
     * @dataProvider existingFieldHandlesDataProvider
     *
     * @param $handle
     */
    public function testExistsInCustomFieldBehavior(string $handle)
    {
        // Make sure it exists
        new CustomFieldBehavior();

        $this->assertInstanceOf(FieldInterface::class, Craft::$app->getFields()->getFieldByHandle($handle));
        $this->assertTrue(property_exists(CustomFieldBehavior::class, $handle));
        $this->assertArrayHasKey($handle, CustomFieldBehavior::$fieldHandles);
    }

    /**
     * Test that adding a field doesnt automatically modify the CustomFieldBehavior
     */
    public function testRetrofittingDontWork()
    {
        $field = new PlainText();
        $field->name = 'testRetrofittingDontWork1';
        $field->handle = 'testRetrofittingDontWork1';

        if (!Craft::$app->getFields()->saveField($field)) {
            throw new InvalidArgumentException("Couldn't save field");
        }

        $cBehavior = new CustomFieldBehavior();
        $this->assertFalse(property_exists($cBehavior, 'testRetrofittingDontWork1'));
        $this->assertArrayHasKey('testRetrofittingDontWork1', CustomFieldBehavior::$fieldHandles);

        // Cleanup and remove the column from the content table.
        if (!Craft::$app->getFields()->deleteField($field)) {
            throw new InvalidArgumentException('Unable to delete field: ' . $field->name . '');
        }
    }

    /**
     * @return array
     * @todo Help needed. Saving fields with fixtures doesnt update the CustomFieldBehavior class props. I cant find a way to solve this.
     *
     */
    public function existingFieldHandlesDataProvider(): array
    {
        return [
            //['testField'],
            //['testField2'],
            //['testField3'],
            //['testField4'],
            //['testField5'],

        ];
    }
}
