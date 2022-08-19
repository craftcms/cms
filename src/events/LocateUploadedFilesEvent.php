<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use yii\base\Event;

/**
 * LocateUploadedFilesEvent event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.2
 */
class LocateUploadedFilesEvent extends Event
{
    /**
     * @var ElementInterface The element being saved
     */
    public ElementInterface $element;

    /**
     * @var array List of files being uploaded for the field.
     *
     * Each file should be represented as an array with the following keys:
     *
     * - `type` – The upload time (`data`, `file`, or `upload`)
     * - `filename` – The filename the asset should have once saved
     * - `data` – The file data string, if `type` is `data`
     * - `path` – The path to the temp file, if `type` is `file` or `upload`
     *
     * Only set `type` to `upload` if the temp file is located within the temp uploads directory, and the
     * file is referenced in `$_FILES`. Otherwise, use `file`.
     */
    public array $files;
}
