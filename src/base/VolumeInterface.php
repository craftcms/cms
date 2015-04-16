<?php
namespace craft\app\base;
use craft\app\errors\VolumeFileExistsException;
use craft\app\errors\VolumeFolderExistsException;


/**
 * Interface VolumeInterface
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.base
 * @since     3.0
 */
interface VolumeInterface
{
	// Static
	// =========================================================================

	/**
	 * Returns whether this source stores files locally on the server.
	 *
	 * @return bool Whether files are stored locally.
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
	 * @param string $directory The path of the directory to list files of.
	 *
	 * @return array
	 */
	public function getFileList($directory);

	/**
	 * Creates a file.
	 *
	 * @param string $path     The path of the file, relative to the source’s root.
	 * @param string $stream   The stream to file
	 *
	 * @throws VolumeFileExistsException
	 * @return bool Whether the operation was successful.
	 */
	public function createFileByStream($path, $stream);

	/**
	 * Updates a file.
	 *
	 * @param string $path     The path of the file, relative to the source’s root.
	 * @param string $stream   The new contents of the file as a stream.
	 *
	 * @return bool Whether the operation was successful.
	 */
	public function updateFileByStream($path, $stream);

	/**
	 * Creates a file, or updates it if it already exists.
	 *
	 * @param string $path     The path of the file, relative to the source’s root.
	 * @param string $contents The contents of the file.
	 *
	 * @throws FileExistsException
	 *
	 * @return bool Whether the operation was successful.
	 */
	public function createOrUpdateFile($path, $contents);

	/**
	 * Returns the contents of a file.
	 *
	 * @param string $path The path of the file, relative to the source’s root.
	 *
	 * @throws FileNotFoundException
	 *
	 * @return string|false The contents of the file, or `false` if the file could not be read.
	 */
	public function getFileContents($path);

	/**
	 * Returns whether a file exists.
	 *
	 * @param string $path The path of the file, relative to the source’s root.
	 *
	 * @return bool Whether the file exists.
	 */
	public function fileExists($path);

	/**
	 * Deletes a file.
	 *
	 * @param string $path The path of the file, relative to the source’s root.
	 *
	 * @return bool Whether the operation was successful.
	 */
	public function deleteFile($path);

	/**
	 * Deletes a file, and returns its former contents.
	 *
	 * @param string $path The path of the file, relative to the source’s root.
	 *
	 * @throws FileNotFoundException
	 *
	 * @return string The contents of the file.
	 */
	public function getContentsAndDeleteFile($path);

	/**
	 * Renames a file.
	 *
	 * @param string $path    The old path of the file, relative to the source’s root.
	 * @param string $newPath The new path of the file, relative to the source’s root.
	 *
	 * @throws FileExistsException
	 * @throws FileNotFoundException
	 *
	 * @return bool Whether the operation was successful.
	 */
	public function renameFile($path, $newPath);

	/**
	 * Copies a file.
	 *
	 * @param string $path    The path of the file, relative to the source’s root.
	 * @param string $newPath The path of the new file, relative to the source’s root.
	 *
	 * @return bool Whether the operation was successful.
	 */
	public function copyFile($path, $newPath);

	/**
	 * Returns a file’s MIME type.
	 *
	 * @param string $path The path of the file, relative to the source’s root.
	 *
	 * @throws FileNotFoundException
	 *
	 * @return string|false The file’s MIME type, or `false` if it could not be determined.
	 */
	public function getMimeType($path);

	/**
	 * Returns a file’s timestamp.
	 *
	 * @param string $path The path of the file, relative to the source’s root.
	 *
	 * @throws FileNotFoundException
	 *
	 * @return string|false The file’s timestamp, or `false` if it could not be determined.
	 */
	public function getTimestamp($path);

	/**
	 * Returns a file’s size.
	 *
	 * @param string $path The path of the file, relative to the source’s root.
	 *
	 * @return int|false The file’s size in bytes, or `false` if it could not be determined.
	 */
	public function getSize($path);

	/**
	 * Creates a directory.
	 *
	 * @param string $path The path of the directory, relative to the source’s root.
	 *
	 * @throws VolumeFolderExistsException
	 * @return bool Whether the operation was successful.
	 */
	public function createDir($path);

	/**
	 * Deletes a directory.
	 *
	 * @param string $path The path of the directory, relative to the source’s root.
	 *
	 * @return bool Whether the operation was successful.
	 */
	public function deleteDir($path);
}
