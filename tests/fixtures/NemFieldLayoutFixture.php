<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use craft\test\fixtures\FieldLayoutFixture as BaseFieldLayoutFixture;

/**
 * Field layouts used by NestedElementManagerTest (owner entry type + its Matrix block entry type).
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class NemFieldLayoutFixture extends BaseFieldLayoutFixture
{
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/nem-field-layout.php';

    /**
     * @inheritdoc
     */
    public $depends = [NemEntryTypeFixture::class];
}
