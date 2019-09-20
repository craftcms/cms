<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use craft\helpers\Assets;

/**
 * Io variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Io
{
    // Public Methods
    // =========================================================================

    /**
     * Return max upload size in bytes.
     *
     * @return int|float
     */
    public function getMaxUploadSize()
    {
        return Assets::getMaxUploadSize();
    }

    /**
     * Returns a list of file kinds.
     *
     * @return array
     */
    public function getFileKinds(): array
    {
        return Assets::getFileKinds();
    }
}
