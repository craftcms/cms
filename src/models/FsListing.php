<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;

/**
 * The FsListings model class.
 *
 * @property-read string $dirname The path to the file
 * @property-read string $basename
 * @property-read string $type "file" or "dir"
 * @property-read string $uri Listing URI
 * @property-read null|int $fileSize
 * @property-read bool $isDir
 * @property-read int $dateModified
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class FsListing extends Model
{
    /**
     * @var string The path for the listing
     */
    private string $dirname;

    /**
     * @var string The filename of the listing
     */
    private string $basename;

    /**
     * @var 'file'|'dir' Type of listing. Can be "file" or "dir".
     */
    private string $type;

    /**
     * @var int|null The filesize.
     */
    private ?int $fileSize = null;

    /**
     * @var int|null Timestamp of date modified.
     */
    private ?int $dateModified = null;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        foreach ($config as $property => $value) {
            if ($property === 'dirname') {
                $value = ltrim($value, './');
            }
            $this->{$property} = $value;
        }

        parent::__construct([]);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getDirname(): string
    {
        return $this->dirname;
    }

    /**
     * @return string
     */
    public function getBasename(): string
    {
        return $this->basename;
    }

    /**
     * @return int|null
     */
    public function getFileSize(): ?int
    {
        return $this->type !== 'dir' ? $this->fileSize : null;
    }

    /**
     * @return int|null
     */
    public function getDateModified(): ?int
    {
        return $this->dateModified;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return ($this->dirname ? "$this->dirname/" : '') . $this->basename;
    }

    /**
     * Return true if this listing is a directory.
     *
     * @return bool
     */
    public function getIsDir(): bool
    {
        return $this->type === 'dir';
    }
}
