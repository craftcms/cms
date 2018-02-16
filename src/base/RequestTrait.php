<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * Request trait.
 * This provides request methods that are common between craft\web\Request and craft\console\Request.
 *
 * @property string $scriptFilename The requested script name being used to access Craft (e.g. “index.php”).
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
trait RequestTrait
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the requested script name being used to access Craft (e.g. “index.php”).
     *
     * @return string
     */
    public function getScriptFilename(): string
    {
        /** @var $this \craft\web\Request|\craft\console\Request */
        return basename($this->getScriptFile());
    }
}
