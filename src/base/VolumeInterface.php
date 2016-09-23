<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\base;

use craft\app\errors\VolumeObjectExistsException;
use craft\app\errors\VolumeObjectNotFoundException;

/**
 * VolumeInterface defines the common interface to be implemented by volume classes.
 *
 * A class implementing this interface should also use [[SavableComponentTrait]] and [[VolumeTrait]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
interface VolumeInterface extends SavableComponentInterface
{
    // Static
    // =========================================================================

    /**
     * Returns whether this source stores files locally on the server.
     *
     * @return boolean Whether files are stored locally.
     */
    public static function isLocal();

    // Public Methods
    // =========================================================================

    /**
     * Returns the URL to the source, if it’s accessible via HTTP traffic.
     *
     * @return string|null The root URL, or `false` if there isn’t one.
     */
    public function getRootUrl();

    /**
     * Returns the root path for the source.
     *
     * @return string|null The root URL, or `false` if there isn’t one.
     */
    public function getRootPath();

    /**
     * List files.
     *
     * @param string  $directory The path of the directory to list files of.
     * @param boolean $recursive whether to fetch file list recursively
     *
     * @return array
     */
    public function getFileList($directory, $recursive = true);

    /**
     * Creates a file.
     *
     * @param string   $path   The path of the file, relative to the source’s root.
     * @param resource $stream The stream to file
     * @param array    $config Additional config options to pass to the adapter.
     *
     * @throws VolumeObjectExistsException if a file already exists at the path on the Volume.
     * @return boolean Whether the operation was successful.
     */
    public function createFileByStream($path, $stream, $config = []);

    /**
     * Updates a file.
     *
     * @param string $path   The path of the file, relative to the source’s root.
     * @param string $stream The new contents of the file as a stream.
     * @param array  $config Additional config options to pass to the adapter.
     *
     * @return boolean Whether the operation was successful.
     */
    public function updateFileByStream($path, $stream, $config = []);

    /**
     * Returns whether a file exists.
     *
     * @param string $path The path of the file, relative to the source’s root.
     *
     * @return boolean Whether the file exists.
     */
    public function fileExists($path);

    /**
     * Deletes a file.
     *
     * @param string $path The path of the file, relative to the source’s root.
     *
     * @return boolean Whether the operation was successful.
     */
    public function deleteFile($path);

    /**
     * Renames a file.
     *
     * @param string $path    The old path of the file, relative to the source’s root.
     * @param string $newPath The new path of the file, relative to the source’s root.
     *
     * @throws VolumeObjectExistsException if a file with such a name exists already.
     * @throws VolumeObjectNotFoundException if the file to be renamed cannot be found.
     * @return boolean Whether the operation was successful.
     */
    public function renameFile($path, $newPath);

    /**
     * Copies a file.
     *
     * @param string $path    The path of the file, relative to the source’s root.
     * @param string $newPath The path of the new file, relative to the source’s root.
     *
     * @return boolean Whether the operation was successful.
     */
    public function copyFile($path, $newPath);

    /**
     * /**
     * Creates a directory.
     *
     * @param string $path The path of the directory, relative to the source’s root.
     *
     * @throws VolumeObjectExistsException if a directory with such name already exists.
     * @return boolean Whether the operation was successful.
     */
    public function createDir($path);

    /**
     * Deletes a directory.
     *
     * @param string $path The path of the directory, relative to the source’s root.
     *
     * @return boolean Whether the operation was successful.
     */
    public function deleteDir($path);

    /**
     * Renames a directory.
     *
     * @param string $path    The path of the directory, relative to the source’s root.
     * @param string $newName The new path of the directory, relative to the source’s root.
     *
     * @throws VolumeObjectExistsException if a directory with such name already exists.
     * @throws VolumeObjectNotFoundException if a directory with such name already exists.
     * @return boolean Whether the operation was successful.
     */
    public function renameDir($path, $newName);

    /**
     * Save a file from the source's uriPath to a local target path.
     *
     * @param $uriPath
     * @param $targetPath
     *
     * @return integer amount of bytes copied
     */
    public function saveFileLocally($uriPath, $targetPath);
}
