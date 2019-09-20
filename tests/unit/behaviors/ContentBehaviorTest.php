<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\behaviors;

use Codeception\Test\Unit;
use Craft;
use craft\base\Field;
use craft\behaviors\ContentBehavior;
use craft\fields\PlainText;
use InvalidArgumentException;
use UnitTester;

/**
 * Unit tests for ContentBehavior
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ContentBehaviorTest extends Unit
{
    // Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    public $tester;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * @dataProvider existingFieldHandlesDataProvider
     *
     * @param $handle
     */
    public function testExistsInContentBehavior(string $handle)
    {
        // Make sure it exists
        new ContentBehavior();

        $this->assertInstanceOf(Field::class, Craft::$app->getFields()->getFieldByHandle($handle));
        $this->assertTrue(property_exists(ContentBehavior::class, $handle));
        $this->assertArrayHasKey($handle, ContentBehavior::$fieldHandles);
    }

    /**
     * Test that adding a field doesnt automatically modify the ContentBehavior
     */
    public function testRetrofittingDontWork()
    {
        $field = new PlainText();
        $field->name = 'testRetrofittingDontWork1';
        $field->handle = 'testRetrofittingDontWork1';

        if (!Craft::$app->getFields()->saveField($field)) {
            throw new InvalidArgumentException("Couldn't save field");
        }

        $cBehavior = new ContentBehavior();
        $this->assertFalse(property_exists($cBehavior, 'testRetrofittingDontWork1'));
        $this->assertArrayHasKey('testRetrofittingDontWork1', ContentBehavior::$fieldHandles);

        // Cleanup and remove the column from the content table.
        if (!Craft::$app->getFields()->deleteField($field)) {
            throw new InvalidArgumentException('Unable to delete field: ' . $field->name . '');
        }
    }

    // Data Providers
    // =========================================================================

    /**
     * @return array
     * @todo Help needed. Saving fields with fixtures doesnt update the ContentBehavior class props. I cant find a way to solve this.
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
