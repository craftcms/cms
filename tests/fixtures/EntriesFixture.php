<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craftunit\fixtures;

use \craft\test\elementfixtures\EntriesFixture as BaseEntriesFixture;

/**
 * Unit tests for ElementsFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class EntriesFixture extends BaseEntriesFixture
{
    public $dataFile = __DIR__.'/data/entries.php';
    public $depends = [SectionsFixture::class, EntryTypeFixture::class];
}