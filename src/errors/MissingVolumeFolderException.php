<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use craft\models\AssetIndexData;
use craft\models\Volume;
use Throwable;
use yii\base\Exception;

/**
 * MissingVolumeFolderException represents an exception caused by a volume folder record that doesn't exist.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class MissingVolumeFolderException extends Exception
{
    /**
     * Constructor
     *
     * @param AssetIndexData $indexEntry
     * @param Volume $volume
     * @param string $folderName
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(public AssetIndexData $indexEntry, public Volume $volume, public string $folderName, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Missing folder';
    }
}
