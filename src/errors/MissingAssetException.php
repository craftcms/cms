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
    public function __construct(public AssetIndexData $indexEntry, public Volume $volume, public VolumeFolder $folder, public string $filename, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
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
