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
    public function load(): void
    {
        parent::load();

        // Refresh here (not in afterLoad()) since Yii2's FixtureTrait::loadFixtures() runs every
        // fixture's load() before calling ANY fixture's afterLoad() - so a dependent fixture (e.g.
        // NemFieldLayoutFixture, which needs to resolve these entry types by UID) would otherwise
        // see a stale entry-types cache during its own load().
        Craft::$app->getEntries()->refreshEntryTypes();
    }

    /**
     * @inheritdoc
     */
    public function unload(): void
    {
        parent::unload();
        Craft::$app->getEntries()->refreshEntryTypes();
    }
}
