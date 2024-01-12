<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\behaviors;

use Craft;
use craft\behaviors\CustomFieldBehavior;
use craft\fields\PlainText;
use craft\test\TestCase;
use InvalidArgumentException;

/**
 * Unit tests for CustomFieldBehavior
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.4.0
 */
class CustomFieldBehaviorTest extends TestCase
{
    /**
     * Test that adding a field doesnt automatically modify the CustomFieldBehavior
     */
    public function testetrofittingDontWork(): void
    {
        $field = new PlainText();
        $field->name = 'testRetrofittingDontWork1';
        $field->handle = 'testRetrofittingDontWork1';

        if (!Craft::$app->getFields()->saveField($field)) {
            throw new InvalidArgumentException("Couldn't save field");
        }

        $cBehavior = new CustomFieldBehavior();
        self::assertFalse(property_exists($cBehavior, 'testRetrofittingDontWork1'));
        self::assertArrayHasKey('testRetrofittingDontWork1', CustomFieldBehavior::$fieldHandles);

        // Cleanup and remove the column from the content table.
        if (!Craft::$app->getFields()->deleteField($field)) {
            throw new InvalidArgumentException('Unable to delete field: ' . $field->name . '');
        }
    }
}
