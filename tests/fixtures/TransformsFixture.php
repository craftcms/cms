<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use Craft;
use craft\records\ImageTransform;
use craft\services\ImageTransforms;
use craft\test\ActiveFixture;

/**
 * Class TransformsFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class TransformsFixture extends ActiveFixture
{
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/transforms.php';

    /**
     * @inheritdoc
     */
    public $modelClass = ImageTransform::class;

    /**
     * @inheritdoc
     */
    public function load(): void
    {
        parent::load();

        Craft::$app->set('assetTransforms', new ImageTransforms());
    }
}
