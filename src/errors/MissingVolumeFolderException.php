<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use craft\base\VolumeInterface;
use craft\models\AssetIndexData;
use craft\models\VolumeFolder;
use Throwable;
use yii\base\Exception;

/**
 * MissingVolumeFolderException represents an exception caused by a volume folder record that doesn't exist.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class MissingVolumeFolderException extends VolumeException
{
    /**
     * @var AssetIndexData
     */
    public $indexEntry;

    /**
     * @var VolumeInterface
     */
    public $volume;

    /**
     * @var string
     */
    public $folderName;

    /**
     * Constructor
     *
     * @param AssetIndexData $indexEntry
     * @param VolumeInterface $volume
     * @param string $folderName
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(AssetIndexData $indexEntry, VolumeInterface $volume, string $folderName, $message = '', $code = 0, Throwable $previous = null)
    {
        $this->indexEntry = $indexEntry;
        $this->volume = $volume;
        $this->folderName = $folderName;
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
