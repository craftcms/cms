<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\console;

use craft\base\RequestTrait;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Request extends \yii\console\Request
{
    // Traits
    // =========================================================================

    use RequestTrait;

    // Public Methods
    // =========================================================================

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
