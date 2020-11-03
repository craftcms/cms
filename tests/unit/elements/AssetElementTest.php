<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements;

use Codeception\Stub\Expected;
use Craft;
use craft\base\Volume;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\User;
use craft\helpers\StringHelper;
use craft\models\AssetTransform;
use craft\services\Users;
use craft\test\TestCase;
use DateInterval;
use DateTime;
use DateTimeZone;
use UnitTester;
use yii\base\Exception;
use yii\validators\InlineValidator;

/**
 * Unit tests for the User Element
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.5
 */
class AssetElementTest extends TestCase
{
    /**
     * @var UnitTester
     */
    public $tester;

    /**
     *
     */
    public function testTransformWithOverrideParameters()
    {
        $asset = $this->make(Asset::class, [
            'getVolume' => $this->make(Volume::class, [
               'hasUrls' => true
            ]),
            'folderId' => 2
        ]);

        $this->tester->mockCraftMethods('assetTransforms', [
            'normalizeTransform' => Expected::once(new AssetTransform()),
            'extendTransform' => Expected::once(new AssetTransform())
        ]);

        $asset->getUrl([
            'transform' => 'transformHandle',
            'width' => 200,
        ]);
    }
}
