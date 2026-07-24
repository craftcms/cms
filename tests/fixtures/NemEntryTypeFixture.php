<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use Craft;
use craft\records\EntryType;
use craft\test\ActiveFixture;

/**
 * Entry types used by NestedElementManagerTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class NemEntryTypeFixture extends ActiveFixture
{
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/nem-entry-types.php';

    /**
     * @inheritdoc
     */
    public $modelClass = EntryType::class;

    /**
     * @inheritdoc
     */
    public function afterLoad()
    {
        Craft::$app->getEntries()->refreshEntryTypes();
    }

    /**
     * @inheritdoc
     */
    public function afterUnload()
    {
        Craft::$app->getEntries()->refreshEntryTypes();
    }
}
