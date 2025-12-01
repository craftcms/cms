<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use craft\records\EntryType;
use craft\test\ActiveFixture;
use CraftCms\Cms\Support\Facades\EntryTypes;

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
        EntryTypes::refreshEntryTypes();
    }

    /**
     * @inheritdoc
     */
    public function afterUnload()
    {
        EntryTypes::refreshEntryTypes();
    }
}
