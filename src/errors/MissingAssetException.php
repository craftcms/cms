<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use craft\models\AssetIndexData;
use craft\models\Volume;
use craft\models\VolumeFolder;
use Throwable;
use yii\base\Exception;

/**
 * MissingAssetException represents an exception caused by an asset record that doesn't exist.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class MissingAssetException extends Exception
{
    /**
     * @var AssetIndexData
     */
    public AssetIndexData $indexEntry;

    /**
     * @var Volume
     */
    public Volume $volume;

    /**
     * @var VolumeFolder
     */
    public VolumeFolder $folder;

    /**
     * @var string
     */
    public string $filename;

    /**
     * Constructor
     *
     * @param AssetIndexData $indexEntry
     * @param Volume $volume
     * @param VolumeFolder $folder
     * @param string $filename
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(AssetIndexData $indexEntry, Volume $volume, VolumeFolder $folder, string $filename, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        $this->indexEntry = $indexEntry;
        $this->volume = $volume;
        $this->folder = $folder;
        $this->filename = $filename;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Missing asset';
    }
}
