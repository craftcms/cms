<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use craft\behaviors\SessionBehavior;

/**
 * Extends [[\yii\web\DbSession]] to add defenses for “headers already sent” errors.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.11.0
 * @mixin SessionBehavior
 */
class DbSession extends \yii\web\DbSession
{
    /**
     * @inheritdoc
     */
    public function has($key): bool
    {
        // don't open the session if the headers were already sent
        if (!$this->getIsActive() && headers_sent()) {
            return isset($_SESSION[$key]);
        }

        return parent::has($key);
    }
}
