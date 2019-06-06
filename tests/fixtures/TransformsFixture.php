<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\fixtures;

use Craft;
use craft\records\AssetTransform;
use craft\services\AssetTransforms;
use craft\test\Fixture;

/**
 * Class TransformsFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class TransformsFixture extends Fixture
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/transforms.php';

    /**
     * @inheritdoc
     */
    public $modelClass = AssetTransform::class;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function load()
    {
        parent::load();

        Craft::$app->set('assetTransforms', new AssetTransforms());
    }
}
