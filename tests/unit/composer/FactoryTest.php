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
use Composer\Util\Loop;
use Composer\Util\ProcessExecutor;
use craft\composer\Factory;
use craft\test\TestCase;

/**
 * Unit tests for craft\composer\Factory
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class FactoryTest extends TestCase
{
    /**
     * @var Factory
     */
    protected Factory $factory;

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        $this->factory = new Factory();
    }

    /**
     * Test creating an archive manager which doesnt have zip and phar archivers. .
     */
    public function testCreateArchiveManager(): void
    {
        $config = new Config();
        $io = new NullIO();
        $downloadManager = new DownloadManager($io);
        $httpDownloader = Factory::createHttpDownloader($io, $config);
        $process = new ProcessExecutor($io);
        $loop = new Loop($httpDownloader, $process);

        $archiveManager = $this->factory->createArchiveManager($config, $downloadManager, $loop);

        self::assertSame([], $this->getInaccessibleProperty($archiveManager, 'archivers'));
    }
}
