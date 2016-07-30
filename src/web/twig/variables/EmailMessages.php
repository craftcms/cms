<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use Craft;
use craft\app\models\RebrandEmail;

Craft::$app->requireEdition(Craft::Client);

/**
 * Email functions.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since      3.0
 * @deprecated in 3.0
 */
class EmailMessages
{
    // Public Methods
    // =========================================================================

    /**
     * Returns all of the system email messages.
     *
     * @return array
     */
    public function getAllMessages()
    {
        Craft::$app->getDeprecator()->log('craft.emailMessages.getAllMessages()', 'craft.emailMessages.getAllMessages() has been deprecated. Use craft.app.emailMessages.allMessages instead.');

        return Craft::$app->getEmailMessages()->getAllMessages();
    }

    /**
     * Returns a system email message by its key.
     *
     * @param string      $key
     * @param string|null $language
     *
     * @return RebrandEmail|null
     */
    public function getMessage($key, $language = null)
    {
        Craft::$app->getDeprecator()->log('craft.emailMessages.getMessage()', 'craft.emailMessages.getMessage() has been deprecated. Use craft.app.emailMessages.getMessage() instead.');

        return Craft::$app->getEmailMessages()->getMessage($key, $language);
    }
}
