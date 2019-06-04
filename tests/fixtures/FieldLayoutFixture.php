<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;


use craft\models\FieldLayout;
use craft\test\fixtures\FieldLayoutFixture as BaseFieldLayoutFixture;

/**
 * Class FieldLayoutFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class FieldLayoutFixture extends BaseFieldLayoutFixture
{
    // Public properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/field-layout.php';

    /**
     * @inheritdoc
     */
    public $modelClass = FieldLayout::class;
}
