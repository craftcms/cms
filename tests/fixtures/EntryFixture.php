<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use craft\test\fixtures\elements\EntryFixture as BaseEntryFixture;

/**
 * Class EntryFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class EntryFixture extends BaseEntryFixture
{
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/entries.php';

    /**
     * @inheritdoc
     */
    public $depends = [SectionsFixture::class, FieldLayoutFixture::class];
}
