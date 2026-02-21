<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Generator;

/**
 * BaseFsInterface defines Craft's legacy file-operation API for filesystem classes and the volume model.
 *
 * New code should prefer Laravel disk operations.
 *
 * @since 4.4.0
 */
interface BaseFsInterface
{
    /**
     * Returns the root URL for a filesystem, if it has one.
     */
    public function getRootUrl(): ?string;

    /**
     * List files.
     */
    public function getFileList(string $directory = '', bool $recursive = true): Generator;

    /**
     * Return the file size.
     */
    public function getFileSize(string $uri): int;

    /**
     * Returns the last time the file was modified.
     */
    public function getDateModified(string $uri): int;

    /**
     * Writes a string to a file.
     */
    public function write(string $path, string $contents, array $config = []): void;

    /**
     * Reads contents of a file to a string.
     */
    public function read(string $path): string;

    /**
     * Writes a file to a fs from a given stream.
     *
     * @param resource $stream
     */
    public function writeFileFromStream(string $path, $stream, array $config = []): void;

    /**
     * Returns whether a file exists.
     */
    public function fileExists(string $path): bool;

    /**
     * Deletes a file.
     */
    public function deleteFile(string $path): void;

    /**
     * Renames a file.
     */
    public function renameFile(string $path, string $newPath, array $config = []): void;

    /**
     * Copies a file.
     */
    public function copyFile(string $path, string $newPath, array $config = []): void;

    /**
     * Gets a stream ready for reading by a file's URI.
     *
     * @return resource
     */
    public function getFileStream(string $uriPath);

    /**
     * Returns whether a directory exists at the given path.
     */
    public function directoryExists(string $path): bool;

    /**
     * Creates a directory.
     */
    public function createDirectory(string $path, array $config = []): void;

    /**
     * Deletes a directory.
     */
    public function deleteDirectory(string $path): void;

    /**
     * Renames a directory.
     */
    public function renameDirectory(string $path, string $newName): void;
}
