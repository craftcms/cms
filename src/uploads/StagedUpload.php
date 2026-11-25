<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\uploads;

use craft\base\FsInterface;
use yii\base\Exception;

/**
 * A file that has been uploaded and is waiting to be processed.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class StagedUpload
{
    /**
     * @param FsInterface $fs The filesystem holding the file
     * @param string $path The file’s path within the filesystem
     * @param string|null $filename The file’s name, if the upload declared one
     * @param int $size The file’s size in bytes
     */
    public function __construct(
        public readonly FsInterface $fs,
        public readonly string $path,
        public readonly ?string $filename,
        public readonly int $size,
    ) {
    }

    /**
     * Copies the file to a local path.
     *
     * @throws Exception if the file can’t be copied in full
     */
    public function copyTo(string $localPath): void
    {
        $source = $this->fs->getFileStream($this->path);
        $target = fopen($localPath, 'wb');

        try {
            // Bounded, in case the stored file changes or misreports its size
            $copied = $target === false ? false : stream_copy_to_stream($source, $target, $this->size + 1);

            if ($copied !== $this->size) {
                throw new Exception('The uploaded file could not be read.');
            }
        } finally {
            fclose($source);
            is_resource($target) && fclose($target);
        }
    }
}
