<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craftunit\fixtures;


use craft\records\EntryType;
use craft\test\Fixture;

/**
 * Unit tests for EntryTypeFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class EntryTypeFixture extends Fixture
{
    public $dataFile = __DIR__.'/data/entry-types.php';
    public $modelClass = EntryType::class;
    public $depends = [SectionsFixture::class];
}