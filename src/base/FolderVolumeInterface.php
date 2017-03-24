<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\base;

use craft\errors\VolumeObjectExistsException;
use craft\errors\VolumeObjectNotFoundException;

/**
 * FolderVolumeInterface extends the common Volume interface and adds folder operations.
 *
 * A class implementing this interface should also use [[SavableComponentTrait]] and [[VolumeTrait]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
interface FolderVolumeInterface extends VolumeInterface
{
    // Public Methods
    // =========================================================================

    /**
     * Returns whether a folder exists at the given path.
     *
     * @param string $path The folder path to check
     *
     * @return bool
     */
    public function folderExists(string $path): bool;

    /**
     *
     * Creates a directory.
     *
     * @param string $path The path of the directory, relative to the source’s root.
     *
     * @throws VolumeObjectExistsException if a directory with such name already exists.
     * @return bool Whether the operation was successful.
     */
    public function createDir(string $path): bool;

    /**
     * Deletes a directory.
     *
     * @param string $path The path of the directory, relative to the source’s root.
     *
     * @return bool Whether the operation was successful.
     */
    public function deleteDir(string $path): bool;

    /**
     * Renames a directory.
     *
     * @param string $path    The path of the directory, relative to the source’s root.
     * @param string $newName The new path of the directory, relative to the source’s root.
     *
     * @throws VolumeObjectExistsException if a directory with such name already exists.
     * @throws VolumeObjectNotFoundException if a directory with such name already exists.
     * @return bool Whether the operation was successful.
     */
    public function renameDir(string $path, string $newName): bool;
}
