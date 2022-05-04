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
     * @var string
     * @phpstan-var class-string<ElementInterface>
     * @since 3.0.30
     */
    protected string $elementType;

    /**
     * @var string|null
     */
    private ?string $_message = null;

    /**
     * @inheritdoc
     */
    public function setElementType(string $elementType): void
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
    public function getTriggerHtml(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getConfirmationMessage(): ?string
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
    public function getMessage(): ?string
    {
        return $this->_message;
    }

    /**
     * Sets the message that should be displayed to the user after the action is performed.
     *
     * @param string $message The message that should be displayed to the user after the action is performed.
     */
    protected function setMessage(string $message): void
    {
        $this->_message = $message;
    }
}
