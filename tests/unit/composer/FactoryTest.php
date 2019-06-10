<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\composer;

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
 * @since 3.2
 */
class FactoryTest extends TestCase
{
    // Properties
    // =========================================================================

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var UnitTester
     */
    protected $tester;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        $this->factory = new Factory();
    }

    // Tests
    // =========================================================================

    /**
     * Test creating an archive manager which doesnt have zip and phar archivers. .
     */
    public function testCreateArchiveManager()
    {
        $config = new Config();
        $downloadManager = new DownloadManager(new NullIO());
        $archiveManager = $this->factory->createArchiveManager($config, $downloadManager);

        $this->assertSame([], $this->getInaccessibleProperty($archiveManager, 'archivers'));
    }
}
