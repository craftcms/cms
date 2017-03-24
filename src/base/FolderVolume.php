<?php
/**
 * The base class for all asset Volumes.  Any Volume type that supports discrete folders must extend this class.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since      3.0
 */

namespace craft\base;

use Craft;
use craft\errors\VolumeObjectExistsException;
use craft\errors\VolumeObjectNotFoundException;

/**
 * Class Volume
 */
abstract class FolderVolume extends Volume implements FolderVolumeInterface
{
    /**
     * @inheritdoc
     */
    public function folderExists(string $path): bool
    {
        return $this->adapter()->has(rtrim($path, '/').($this->foldersHaveTrailingSlashes ? '/' : ''));
    }

    /**
     * @inheritdoc
     */
    public function createDir(string $path): bool
    {
        if ($this->folderExists($path)) {
            throw new VolumeObjectExistsException(Craft::t('app',
                'Folder “{folder}” already exists on the volume!',
                ['folder' => $path]));
        }

        return $this->filesystem()->createDir($path);
    }

    /**
     * @inheritdoc
     */
    public function deleteDir(string $path): bool
    {
        try {
            return $this->filesystem()->deleteDir($path);
        } catch (\Exception $exception) {
            // We catch all Exceptions because most of the times these will be 3rd party exceptions.
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function renameDir(string $path, string $newName): bool
    {
        if (!$this->folderExists($path)) {
            throw new VolumeObjectNotFoundException(Craft::t('app',
                'Folder “{folder}” cannot be found on the volume.',
                ['folder' => $path]));
        }

        return $this->filesystem()->rename($path, $newName);
    }
}
