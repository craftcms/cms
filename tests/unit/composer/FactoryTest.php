<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\composer;

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
 * @since 3.1
 */
class FactoryTest extends TestCase
{
    // Properties
    // =========================================================================

    /**
     * @var Factory $factory
     */
    protected $factory;

    /**
     * @var UnitTester $tester
     */
    protected $tester;

    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function _before()
    {
        $this->factory = new Factory();
    }

    // Tests
    // =========================================================================

    /**
     * Test creating an archive manager.
     *
     * @todo Test creation without DownloadManager passed in?
     */
    public function testCreateArchiveManager()
    {
        $config = new Config();
        $downloadManager = new DownloadManager(new NullIO());
        $archiveManager = $this->factory->createArchiveManager($config, $downloadManager);

        // Ensure that zip and phar archivers aren't added.
        $this->assertSame([], $this->getInaccessibleProperty($archiveManager, 'archivers'));
    }
}