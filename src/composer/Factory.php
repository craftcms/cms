<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\composer;

use Composer\Config;
use Composer\Downloader\DownloadManager;
use Composer\Package\Archiver;
use Composer\Util\Loop;

/**
 * Composer Factory
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Factory extends \Composer\Factory
{
    /**
     * Copied from \Composer\Factory::createArchiveManager(), but without adding the zip/phar archivers
     * to avoid unnecessary server requirements.
     *
     * Full class names used when the parent implementation referenced classes relative to its own namespace.
     *
     * @param Config $config The configuration
     * @param DownloadManager $dm Manager use to download sources
     * @param Loop $loop
     * @return Archiver\ArchiveManager
     */
    public function createArchiveManager(Config $config, DownloadManager $dm, Loop $loop)
    {
        $am = new Archiver\ArchiveManager($dm, $loop);
        // $am->addArchiver(new Archiver\ZipArchiver);
        // $am->addArchiver(new Archiver\PharArchiver);

        return $am;
    }
}
