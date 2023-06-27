<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use craft\test\fixtures\elements\EntryFixture as BaseEntryFixture;

/**
 * Class EntryWithMatrixFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.10
 */
class EntryWithMatrixFixture extends BaseEntryFixture
{
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/entry-with-matrix.php';

    /**
     * @inheritdoc
     */
    public $depends = [EntryFixture::class];
}
