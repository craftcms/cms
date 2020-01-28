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
 * @since 3.0.0
 */
class Request extends \yii\console\Request
{
    use RequestTrait;

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
            foreach (['web', 'public', 'public_html', 'html'] as $folder) {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $folder)) {
                    $dir .= DIRECTORY_SEPARATOR . $folder;
                    break;
                }
            }
            Craft::setAlias('@webroot', $dir);
            $this->isWebrootAliasSetDynamically = true;
        }
        if (Craft::getRootAlias('@web') === false) {
            Craft::setAlias('@web', '/');
            $this->isWebAliasSetDynamically = true;
        }
    }

    /**
     * Returns whether the control panel was requested. (Narrator: It wasn't.)
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
     * Returns whether this was a Login request.
     *
     * @return bool
     * @since 3.2.0
     */
    public function getIsLoginRequest(): bool
    {
        return false;
    }

    /**
     * Returns whether the current request is solely an action request. (Narrator: It isn't.)
     *
     * @return bool
     * @deprecated in 3.2.0
     */
    public function getIsSingleActionRequest(): bool
    {
        return false;
    }

    /**
     * Returns whether this is an element preview request.
     *
     * @return bool
     * @since 3.2.1
     */
    public function getIsPreview(): bool
    {
        return false;
    }

    /**
     * Returns whether this is a Live Preview request. (Narrator: It isn't.)
     *
     * @return bool Whether this is a Live Preview request.
     * @deprecated in 3.2.0
     */
    public function getIsLivePreview(): bool
    {
        return false;
    }

    /**
     * Returns the token submitted with the request, if there is one.
     *
     * @return string|null The token, or `null` if there isn’t one.
     * @since 3.2.0
     */
    public function getToken()
    {
        return null;
    }
}
