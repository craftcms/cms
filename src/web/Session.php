<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web;

use craft\behaviors\SessionBehavior;

/**
 * Extends \yii\web\Session to add support for setting the session folder and creating it if it doesn’t exist.
 *
 * An instance of the HttpSession service is globally accessible in Craft via [[Application::httpSession `Craft::$app->getSession()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 * @mixin SessionBehavior
 */
class Session extends \yii\web\Session
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            SessionBehavior::class,
        ];
    }
}
