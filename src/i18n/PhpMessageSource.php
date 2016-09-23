<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\i18n;

use Craft;

/**
 * Class PhpMessageSource
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class PhpMessageSource extends \yii\i18n\PhpMessageSource
{
    // Properties
    // =========================================================================

    /**
     * @var boolean Whether the messages can be overridden by translations in the site’s translations folder
     */
    public $allowOverrides = false;

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function loadMessages($category, $language)
    {
        $messages = parent::loadMessages($category, $language);

        if ($this->allowOverrides) {
            $overrideMessages = $this->_loadOverrideMessages($category, $language);
            $messages = array_merge($messages, $overrideMessages);
        }

        return $messages;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the override methods defined in the site’s translations folder.
     *
     * @param string $category
     * @param string $language
     *
     * @return array|null
     */
    private function _loadOverrideMessages($category, $language)
    {
        $basePath = $this->basePath;
        $this->basePath = Craft::getAlias('@translations');

        // Code adapted from yii\i18n\PhpMessageSource, minus the error logging
        $messageFile = $this->getMessageFilePath($category, $language);
        $messages = $this->loadMessagesFromFile($messageFile);

        $fallbackLanguage = substr($language, 0, 2);
        if ($fallbackLanguage != $language) {
            $fallbackMessageFile = $this->getMessageFilePath($category, $fallbackLanguage);
            $fallbackMessages = $this->loadMessagesFromFile($fallbackMessageFile);

            if (empty($messages)) {
                $messages = $fallbackMessages;
            } else if (!empty($fallbackMessages)) {
                foreach ($fallbackMessages as $key => $value) {
                    if (!empty($value) && empty($messages[$key])) {
                        $messages[$key] = $fallbackMessages[$key];
                    }
                }
            }
        }

        $this->basePath = $basePath;

        return (array)$messages;
    }
}
