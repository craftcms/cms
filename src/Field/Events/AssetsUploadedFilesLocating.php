<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\Contracts\FieldInterface;

/** @phpstan-import-type UploadedFileData from Assets */
class AssetsUploadedFilesLocating
{
    public function __construct(
        public FieldInterface $field,

        /** @var ElementInterface The element being saved */
        public ElementInterface $element,

        /**
         * @var list<UploadedFileData> List of files being uploaded for the field.
         *
         * Each file should be represented as an array with the following keys:
         *
         * - `type` – The upload time (`data`, `file`, or `upload`)
         * - `filename` – The filename the asset should have once saved
         * - `mimeType` – The MIME type that was provided by the request
         * - `data` – The file data string, if `type` is `data`
         * - `path` – The path to the temp file, if `type` is `file` or `upload`
         *
         * Only set `type` to `upload` if the temp file is located within the temp uploads directory, and the
         * file is referenced in `$_FILES`. Otherwise, use `file`.
         */
        public array $files,
    ) {}
}
