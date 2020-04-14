<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\composer;

use Composer\Package\Archiver;

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
     * @param \Composer\Config $config
     * @param \Composer\Downloader\DownloadManager|null $dm
     * @return Archiver\ArchiveManager
     */
    public function createArchiveManager(\Composer\Config $config, \Composer\Downloader\DownloadManager $dm = null)
    {
        if (null === $dm) {
            $io = new \Composer\IO\NullIO();
            $io->loadConfiguration($config);
            $dm = $this->createDownloadManager($io, $config);
        }

        $am = new Archiver\ArchiveManager($dm);
//        $am->addArchiver(new Archiver\ZipArchiver);
//        $am->addArchiver(new Archiver\PharArchiver);

        return $am;
    }
}
