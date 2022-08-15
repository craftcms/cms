<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements\db;

use craft\elements\Entry;
use craft\models\UserGroup;
use craft\test\TestCase;

/**
 * Unit tests for entry queries.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 4.0.0
 */
class EntryQueryTest extends TestCase
{
    /**
     *
     */
    public function testAuthorGroup(): void
    {
        $query = Entry::find();

        $group1 = new UserGroup(['id' => 1, 'handle' => 'foo']);
        $group2 = new UserGroup(['id' => 2, 'handle' => 'bar']);
        $group3 = new UserGroup(['id' => 3, 'handle' => 'baz']);

        $query->authorGroup($group1);
        $this->assertSame(1, $query->authorGroupId);

        $query->authorGroup([$group1, $group2, $group3]);
        $this->assertSame([1, 2, 3], $query->authorGroupId);
    }
}
