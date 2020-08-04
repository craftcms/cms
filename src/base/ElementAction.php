<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\elements\db\ElementQueryInterface;

/**
 * ElementAction is the base class for classes representing element actions in terms of objects.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class ElementAction extends ConfigurableComponent implements ElementActionInterface
{
    /**
     * @inheritdoc
     */
    public static function isDestructive(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function isDownload(): bool
    {
        return false;
    }

    /**
     * @var string|ElementInterface
     *
     * @since 3.0.30
     */
    protected $elementType;

    /**
     * @var
     */
    private $_message;

    /**
     * @inheritdoc
     */
    public function setElementType(string $elementType)
    {
        $this->elementType = $elementType;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return static::displayName();
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getConfirmationMessage()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getMessage()
    {
        return $this->_message;
    }

    /**
     * Sets the message that should be displayed to the user after the action is performed.
     *
     * @param string $message The message that should be displayed to the user after the action is performed.
     */
    protected function setMessage(string $message)
    {
        $this->_message = $message;
    }
}
