<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;

/**
 * Delete represents a Delete element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Delete extends ElementAction
{
    /**
     * @var string|null The confirmation message that should be shown before the elements get deleted
     */
    public $confirmationMessage;

    /**
     * @var string|null The message that should be shown after the elements get deleted
     */
    public $successMessage;

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Delete');
    }

    /**
     * @inheritdoc
     */
    public static function isDestructive(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getConfirmationMessage()
    {
        if ($this->confirmationMessage !== null) {
            return $this->confirmationMessage;
        }

        /** @var ElementInterface|string $elementType */
        $elementType = $this->elementType;

        return Craft::t('app', 'Are you sure you want to delete the selected {type}?', [
            'type' => $elementType::pluralLowerDisplayName(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $elementsService = Craft::$app->getElements();
        foreach ($query->all() as $element) {
            $elementsService->deleteElement($element);
        }

        if ($this->successMessage !== null) {
            $this->setMessage($this->successMessage);
        } else {
            /** @var ElementInterface|string $elementType */
            $elementType = $this->elementType;
            $this->setMessage(Craft::t('app', '{type} deleted.', [
                'type' => $elementType::pluralDisplayName(),
            ]));
        }

        return true;
    }
}
