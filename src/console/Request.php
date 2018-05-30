<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console;

use Craft;
use craft\base\RequestTrait;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Request extends \yii\console\Request
{
    // Traits
    // =========================================================================

    use RequestTrait;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Set the @webroot and @web aliases, in case they are needed
        if (Craft::getRootAlias('@webroot') === false) {
            // see if it's any of the usual suspects
            $dir = dirname($this->getScriptFile());
            foreach (['web', 'public', 'public_html'] as $folder) {
                if ($found = (is_dir($dir.DIRECTORY_SEPARATOR.$folder))) {
                    $dir .= DIRECTORY_SEPARATOR.$folder;
                    break;
                }
            }
            Craft::setAlias('@webroot', $dir);
        }
        if (Craft::getRootAlias('@web') === false) {
            Craft::setAlias('@web', '/');
        }
    }

    /**
     * Returns whether the Control Panel was requested. (Narrator: It wasn't.)
     *
     * @return bool
     */
    public function getIsCpRequest(): bool
    {
        return false;
    }

    /**
     * Returns whether the front end site was requested. (Narrator: It wasn't.)
     *
     * @return bool
     */
    public function getIsSiteRequest(): bool
    {
        return false;
    }

    /**
     * Returns whether a specific controller action was requested. (Narrator: There wasn't.)
     *
     * @return bool
     */
    public function getIsActionRequest(): bool
    {
        return false;
    }

    /**
     * Returns whether the current request is solely an action request. (Narrator: It isn't.)
     */
    public function getIsSingleActionRequest()
    {
        return false;
    }

    /**
     * Returns whether this is a Live Preview request. (Narrator: It isn't.)
     *
     * @return bool Whether this is a Live Preview request.
     */
    public function getIsLivePreview(): bool
    {
        return false;
    }
}
