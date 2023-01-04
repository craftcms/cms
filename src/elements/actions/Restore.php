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
 * Restore represents a Restore element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 */
class Restore extends ElementAction
{
    /**
     * @var string|null The message that should be shown after the elements get restored
     */
    public ?string $successMessage = null;

    /**
     * @var string|null The message that should be shown after some elements get restored
     */
    public ?string $partialSuccessMessage = null;

    /**
     * @var string|null The message that should be shown if no elements get restored
     */
    public ?string $failMessage = null;

    /**
     * @var bool Whether the action should only be available for elements with a `data-restorable` attribute
     * @since 4.3.0
     */
    public bool $restorableElementsOnly = false;

    /**
     * @inheritdoc
     */
    public function setElementType(string $elementType): void
    {
        /** @var string|ElementInterface $elementType */
        /** @phpstan-var class-string<ElementInterface> $elementType */
        parent::setElementType($elementType);

        if (!isset($this->successMessage)) {
            $this->successMessage = Craft::t('app', '{type} restored.', [
                'type' => $elementType::pluralDisplayName(),
            ]);
        }

        if (!isset($this->partialSuccessMessage)) {
            $this->partialSuccessMessage = Craft::t('app', 'Some {type} restored.', [
                'type' => $elementType::pluralLowerDisplayName(),
            ]);
        }

        if (!isset($this->failMessage)) {
            $this->failMessage = Craft::t('app', '{type} not restored.', [
                'type' => $elementType::pluralDisplayName(),
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Restore');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        if ($this->restorableElementsOnly) {
            // Only enable for deletable elements, per canDelete()
            Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        validateSelection: \$selectedItems => {
            for (let i = 0; i < \$selectedItems.length; i++) {
                if (!Garnish.hasAttr(\$selectedItems.eq(i).find('.element'), 'data-restorable')) {
                    return false;
                }
            }
            return true;
        },
    });
})();
JS, [static::class]);
        }

        return '<div class="btn formsubmit">' . $this->getTriggerLabel() . '</div>';
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $anySuccess = false;
        $anyFail = false;
        $elementsService = Craft::$app->getElements();
        foreach ($query->all() as $element) {
            if ($elementsService->restoreElement($element)) {
                $anySuccess = true;
            } else {
                $anyFail = true;
            }
        }

        if (!$anySuccess && $anyFail) {
            $this->setMessage($this->failMessage);
            return false;
        }

        if ($anyFail) {
            $this->setMessage($this->partialSuccessMessage);
        } else {
            $this->setMessage($this->successMessage);
        }

        return true;
    }
}
