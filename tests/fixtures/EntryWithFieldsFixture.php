<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use craft\test\fixtures\elements\EntryFixture as BaseEntryFixture;

/**
 * Class EntryWithFieldsFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class EntryWithFieldsFixture extends BaseEntryFixture
{
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/entry-with-fields.php';

    /**
     * @inheritdoc
     */
    public $depends = [FieldLayoutFixture::class, SectionsFixture::class];
}
