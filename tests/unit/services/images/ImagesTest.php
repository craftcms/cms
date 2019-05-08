<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\services;


use Codeception\Test\Unit;
use craft\helpers\ConfigHelper;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\services\Images;


/**
 * Unit tests for ImagesTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class ImagesTest extends Unit
{
    /**
     * @var \UnitTester $tester
     */
    protected $tester;

    /**
     * @var Images $gc
     */
    protected $images;

    /**
     * @var string $path
     */
    protected $path;

    /**
     * @var string $sandboxPath
     */
    protected $sandboxPath;

    public function _before()
    {
        $this->path = dirname(__DIR__, 3).'/_data/assets/files/';
        $this->sandboxPath = dirname(__DIR__).'/sandbox/';

        parent::_before();
        $this->images = \Craft::$app->getImages();

        if (!is_dir($this->sandboxPath)) {
            FileHelper::createDirectory($this->sandboxPath);
        }

        FileHelper::clearDirectory($this->sandboxPath);

        copy($this->path.'unclean/dirty-svg.svg', $this->sandboxPath.'dirty-svg.svg');

    }

    /**
     * @dataProvider memoryForImageData
     * @param $result
     * @param $filePath
     */
    public function testCheckMemoryForImage($result, $filePath)
    {
        $memory = $this->images->checkMemoryForImage($this->path.$filePath, false);
        $this->assertSame($memory, $result);
    }
    public function memoryForImageData()
    {
        return [
            [true, 'craft-logo.svg'],
            [true, 'empty-file.text'],

            // TODO: Can we get this to fail
        ];
    }

    public function testCleanImageSvg()
    {
        // http://svg.enshrined.co.uk/
        $this->images->cleanImage(
            $this->sandboxPath.'dirty-svg.svg'
        );

        $contents = file_get_contents($this->sandboxPath.'dirty-svg.svg');
        $this->assertFalse(
            StringHelper::contains($contents, '<script>')
        );
        $this->assertFalse(
            StringHelper::contains($contents, '<this>')
        );
    }
    public function testDontCleanWithConfigSetting()
    {
        \Craft::$app->getConfig()->getGeneral()->sanitizeSvgUploads = false;

        // http://svg.enshrined.co.uk/
        $this->images->cleanImage(
            $this->sandboxPath.'dirty-svg.svg'
        );

        $contents = file_get_contents($this->sandboxPath.'dirty-svg.svg');
        $this->assertTrue(
            StringHelper::contains($contents, '<script>')
        );
        $this->assertTrue(
            StringHelper::contains($contents, '<this>')
        );
    }
}
