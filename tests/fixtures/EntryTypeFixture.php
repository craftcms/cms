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
 * Class EntryTypeFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class EntryTypeFixture extends ActiveFixture
{
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/entry-types.php';

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
