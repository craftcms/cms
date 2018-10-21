<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craftunit\helpers;


use Codeception\Test\Unit;
use craft\helpers\Assets;
use craftunit\fixtures\AssetsFixture;
use craftunit\fixtures\VolumesFolderFixture;
use craftunit\fixtures\VolumesFixture;

/**
 * Class AssetsHelper.
 *
s * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class AssetsHelperTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'volumes' => [
                'class' => VolumesFixture::class,
            ],
            'volumes-folder' => [
                'class' => VolumesFolderFixture::class,
            ],
            'assets' => [
                'class' => AssetsFixture::class,
            ],
        ];
    }

    /**
     * @param $result
     * @param $input
     *
     * @throws \yii\base\Exception
     */
    public function testTempFilePath()
    {
        $tempPath = Assets::tempFilePath();
        $this->assertNotFalse(strpos($tempPath, '\_craft\storage\runtime\temp'));
        $tempPath = Assets::tempFilePath('test');
        $this->assertNotFalse(strpos($tempPath, '.test'));
    }

    public function testGenerateUrl()
    {

    }
}