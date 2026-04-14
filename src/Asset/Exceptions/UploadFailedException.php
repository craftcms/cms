<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Exceptions;

use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use Throwable;

use function CraftCms\Cms\t;

class UploadFailedException extends FilesystemException
{
    /**
     * Constructor
     */
    public function __construct(public int $errorCode = 0, ?string $message = null, ?Throwable $previous = null)
    {
        if ($message === null) {
            $message = match ($this->errorCode) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => t('The uploaded file exceeds the maximum allowed size.'),
                UPLOAD_ERR_PARTIAL, UPLOAD_ERR_NO_FILE => t('The file failed to upload to the server properly.'),
                UPLOAD_ERR_NO_TMP_DIR => t('Could not write to the temporary upload folder.'),
                UPLOAD_ERR_CANT_WRITE => t('There was a problem with writing the file to the disk.'),
                default => t('There was a problem with uploading the file.'),
            };
        }

        parent::__construct($message, 0, $previous);
    }
}
