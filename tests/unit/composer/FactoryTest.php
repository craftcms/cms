<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craftunit\composer;


use Codeception\Test\Unit;
use Composer\Config;
use Composer\Downloader\DownloadManager;
use Composer\IO\NullIO;
use craft\composer\Factory;
use craft\test\TestCase;
use UnitTester;

/**
 * Unit tests for craft\composer\Factory
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class FactoryTest extends TestCase
{
    /**
     * @var Factory $factory
     */
    protected $factory;

    /**
     * @var UnitTester $tester
     */
    protected $tester;

    public function _before()
    {
        $this->factory = new Factory();
    }

    /**
     * TODO: Test creation without DownloadManager passed in?
     */
    public function testCreateArchiveManager()
    {
        $config = new Config();
        $downloadManager = new DownloadManager(new NullIO());
        $archiveManager = $this->factory->createArchiveManager($config, $downloadManager);

        // Ensure that zip and phar archivers arent added.
        $this->assertSame([], $this->getInaccessibleProperty($archiveManager, 'archivers'));
    }
}