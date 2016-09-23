<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\base;

/**
 * Request trait.
 *
 * This provides request methods that are common between craft\app\web\Request and craft\app\console\Request.
 *
 * @property string $scriptFilename The requested script name being used to access Craft (e.g. “index.php”).
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
    public function getScriptFilename()
    {
        /** @var $this \craft\app\web\Request|\craft\app\console\Request */
        return basename($this->getScriptFile());
    }
}
