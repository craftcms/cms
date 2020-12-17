<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\base\VolumeInterface;
use craft\volumes\Temp;
use yii\base\InvalidConfigException;

/**
 * The VolumeListingMetadata model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class VolumeListingMetadata extends Model
{
    /**
     * @var int|null
     */
    private ?int $lastModified;

    /**
     * @var int|null
     */
    private ?int $filesize;

    /**
     * @var string|null
     */
    private ?string $mimeType;

    /**
     * Array holding additional meta data, if any.
     * @var array|mixed
     */
    private array $additionalMetadata;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        $this->lastModified = $config['lastModified'] ?? null;
        $this->filesize = $config['filesize'] ?? null;
        $this->mimeType = $config['mimeType'] ?? '';
        $this->additionalMetadata = $config['additionalMetadata'] ?? [];

        parent::__construct([]);
    }

    /**
     * @return VolumeListing|mixed
     */
    public function getVolumeListing(): VolumeListing
    {
        return $this->volumeListing;
    }

    /**
     * @return int|mixed|null
     */
    public function getLastModified(): ?int
    {
        return $this->lastModified;
    }

    /**
     * @return int|mixed|null
     */
    public function getFilesize(): ?int
    {
        return $this->filesize;
    }

    /**
     * @return mixed|string|null
     */
    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    /**
     * @return array|mixed
     */
    public function getAdditionalMetadata(): array
    {
        return $this->additionalMetadata;
    }
}
