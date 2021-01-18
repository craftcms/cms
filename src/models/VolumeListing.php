<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use craft\base\VolumeInterface;

/**
 * The VolumeListing model class.
 *
 * @property-read string $path The path to the file
 * @property-read string $filename
 * @property-read string $type "file" or "dir"
 * @property-read VolumeInterface $volume The volume containing the listing.
 * @property-read string $uri Listing URI
 * @property-read null|int $fileSize
 * @property-read int $dateModified
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class VolumeListing extends Model
{
    /**
     * @var string The path for the listing
     */
    private string $path;

    /**
     * @var string The filename of the listing
     */
    private string $filename;

    /**
     * @var string Type of listing. Can be "file" or "dir".
     */
    private string $type;

    /**
     * @var int|null The filesize.
     */
    private ?int $fileSize;

    /**
     * @var int|null Timestamp of date modified.
     */
    private int $dateModified;

    private VolumeInterface $volume;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        $this->path = $config['path'];
        $this->filename = $config['filename'];
        $this->type = $config['type'];
        $this->volume = $config['volume'];
        $this->dateModified = $config['dateModified'] ?? null;

        if ($this->type === 'file') {
            $this->fileSize = $config['fileSize'];
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
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return int|null
     */
    public function getFileSize(): ?int
    {
        return $this->type !== 'dir' ? $this->fileSize : null;
    }

    /**
     * @return int
     */
    public function getDateModified(): int
    {
        return $this->dateModified;
    }

    /**
     * @return VolumeInterface|mixed
     */
    public function getVolume(): VolumeInterface
    {
        return $this->volume;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->path . ($this->path ? DIRECTORY_SEPARATOR : '') . $this->filename;
    }
}
