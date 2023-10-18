<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\errors\FsException;
use craft\errors\FsObjectNotFoundException;
use Generator;

/**
 * BaseFsInterface defines the common interface to be implemented by filesystem classes and volume model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
interface BaseFsInterface
{
    /**
     * Returns the root URL for a filesystem, if it has one.
     *
     * @return string|null
     */
    public function getRootUrl(): ?string;

    /**
     * List files.
     *
     * @param string $directory The path of the directory to list files of
     * @param bool $recursive whether to fetch file list recursively, defaults to true
     * @return Generator
     * @throws FsException
     */
    public function getFileList(string $directory = '', bool $recursive = true): Generator;

    /**
     * Return the file size.
     *
     * @param string $uri
     * @return int
     * @throws FsException
     */
    public function getFileSize(string $uri): int;

    /**
     * Returns the last time the file was modified.
     *
     * @param string $uri
     * @return int
     * @throws FsException
     */
    public function getDateModified(string $uri): int;


    /**
     * Writes a string to a file.
     *
     * @param string $path The path of the file
     * @param string $contents The file contents to write
     * @param array $config Additional config options to pass on
     * @throws FsException
     */
    public function write(string $path, string $contents, array $config = []): void;

    /**
     * Reads contents of a file to a string.
     *
     * @param string $path The path of the file
     * @return string
     */
    public function read(string $path): string;

    /**
     * Writes a file to a fs from a given stream.
     *
     * @param string $path The path of the file, relative to the source’s root
     * @param resource $stream The new contents of the file as a stream
     * @param array $config Additional config options to pass on
     * @throws FsException
     */
    public function writeFileFromStream(string $path, $stream, array $config = []): void;

    /**
     * Returns whether a file exists.
     *
     * @param string $path The path of the file, relative to the source’s root
     * @return bool
     * @throws FsException
     */
    public function fileExists(string $path): bool;

    /**
     * Deletes a file.
     *
     * @param string $path The path of the file, relative to the source’s root
     */
    public function deleteFile(string $path): void;

    /**
     * Renames a file.
     *
     * @param string $path The old path of the file, relative to the source’s root
     * @param string $newPath The new path of the file, relative to the source’s root
     * @throws FsException
     */
    public function renameFile(string $path, string $newPath): void;

    /**
     * Copies a file.
     *
     * @param string $path The path of the file, relative to the source’s root
     * @param string $newPath The path of the new file, relative to the source’s root
     * @throws FsException
     */
    public function copyFile(string $path, string $newPath): void;

    /**
     * Gets a stream ready for reading by a file's URI.
     *
     * @param string $uriPath
     * @return resource
     * @throws FsException if a stream cannot be created
     */
    public function getFileStream(string $uriPath);

    /**
     * Returns whether a directory exists at the given path.
     *
     * @param string $path The directory path to check
     * @return bool
     * @throws FsException
     */
    public function directoryExists(string $path): bool;

    /**
     * Creates a directory.
     *
     * @param string $path The path of the directory, relative to the source’s root
     * @param array $config The config to use
     * @throws FsException
     */
    public function createDirectory(string $path, array $config = []): void;

    /**
     * Deletes a directory.
     *
     * @param string $path The path of the directory, relative to the source’s root
     * @throws FsException
     */
    public function deleteDirectory(string $path): void;

    /**
     * Renames a directory.
     *
     * @param string $path The path of the directory, relative to the source’s root
     * @param string $newName The new path of the directory, relative to the source’s root
     * @throws FsObjectNotFoundException if directory cannot be found
     */
    public function renameDirectory(string $path, string $newName): void;
}
