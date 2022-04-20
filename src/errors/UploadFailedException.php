<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use Craft;
use Throwable;

/**
 * Class UploadFailedException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UploadFailedException extends FileException
{
    /**
     * @var int Error code
     */
    public int $errorCode;

    /**
     * Constructor
     *
     * @param int $errorCode
     * @param string|null $message
     * @param Throwable|null $previous
     */
    public function __construct(int $errorCode = 0, ?string $message = null, Throwable $previous = null)
    {
        $this->errorCode = $errorCode;

        if ($message === null) {
            $message = match ($errorCode) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => Craft::t('app', 'The uploaded file exceeds the maximum allowed size.'),
                UPLOAD_ERR_PARTIAL, UPLOAD_ERR_NO_FILE => Craft::t('app', 'The file failed to upload to the server properly.'),
                UPLOAD_ERR_NO_TMP_DIR => Craft::t('app', 'Could not write to the temporary upload folder.'),
                UPLOAD_ERR_CANT_WRITE => Craft::t('app', 'There was a problem with writing the file to the disk.'),
                default => Craft::t('app', 'There was a problem with uploading the file.'),
            };
        }

        parent::__construct($message, 0, $previous);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Upload failed';
    }
}
