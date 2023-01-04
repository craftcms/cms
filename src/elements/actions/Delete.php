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
use craft\db\Table;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Db;
use craft\helpers\Html;

/**
 * Delete represents a Delete element action.
 *
 * Element types that make this action available should implement [[ElementInterface::canDelete()]] to explicitly state whether they can be
 * deleted by the current user.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Delete extends ElementAction implements DeleteActionInterface
{
    /**
     * @var bool Whether to delete the element’s descendants as well.
     * @since 3.5.0
     */
    public bool $withDescendants = false;

    /**
     * @var bool Whether to permanently delete the elements.
     * @since 3.5.0
     */
    public bool $hard = false;

    /**
     * @var string|null The confirmation message that should be shown before the elements get deleted
     */
    public ?string $confirmationMessage = null;

    /**
     * @var string|null The message that should be shown after the elements get deleted
     */
    public ?string $successMessage = null;

    /**
     * @inheritdoc
     */
    public function canHardDelete(): bool
    {
        return !$this->withDescendants;
    }

    /**
     * @inheritdoc
     */
    public function setHardDelete(): void
    {
        $this->hard = true;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getTriggerHtml(): ?string
    {
        // Only enable for deletable elements, per canDelete()
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        validateSelection: \$selectedItems => {
            for (let i = 0; i < \$selectedItems.length; i++) {
                if (!Garnish.hasAttr(\$selectedItems.eq(i).find('.element'), 'data-deletable')) {
                    return false;
                }
            }
            return true;
        },
    });
})();
JS, [static::class]);

        if ($this->hard) {
            return Html::tag('div', $this->getTriggerLabel(), [
                'class' => ['btn', 'formsubmit'],
            ]);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        if ($this->hard) {
            return Craft::t('app', 'Delete permanently');
        }

        if ($this->withDescendants) {
            return Craft::t('app', 'Delete (with descendants)');
        }

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
    public function getConfirmationMessage(): ?string
    {
        if (isset($this->confirmationMessage)) {
            return $this->confirmationMessage;
        }

        /** @var ElementInterface|string $elementType */
        $elementType = $this->elementType;

        if ($this->hard) {
            return Craft::t('app', 'Are you sure you want to permanently delete the selected {type}?', [
                'type' => $elementType::pluralLowerDisplayName(),
            ]);
        }

        if ($this->withDescendants) {
            return Craft::t('app', 'Are you sure you want to delete the selected {type} along with their descendants?', [
                'type' => $elementType::pluralLowerDisplayName(),
            ]);
        }

        return Craft::t('app', 'Are you sure you want to delete the selected {type}?', [
            'type' => $elementType::pluralLowerDisplayName(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $withDescendants = $this->withDescendants && !$this->hard;
        $elementsService = Craft::$app->getElements();

        if ($withDescendants) {
            $query
                ->with([
                    [
                        'descendants',
                        [
                            'orderBy' => ['structureelements.lft' => SORT_DESC],
                        ],
                    ],
                ])
                ->orderBy(['structureelements.lft' => SORT_DESC]);
        }

        $deletedElementIds = [];
        $user = Craft::$app->getUser()->getIdentity();

        foreach ($query->all() as $element) {
            if (!$elementsService->canDelete($element, $user)) {
                continue;
            }
            if (!isset($deletedElementIds[$element->id])) {
                if ($withDescendants) {
                    foreach ($element->getDescendants()->all() as $descendant) {
                        if (!isset($deletedElementIds[$descendant->id]) && $elementsService->canDelete($descendant, $user)) {
                            $elementsService->deleteElement($descendant);
                            $deletedElementIds[$descendant->id] = true;
                        }
                    }
                }
                $elementsService->deleteElement($element);
                $deletedElementIds[$element->id] = true;
            }
        }

        if ($this->hard) {
            if (!empty($deletedElementIds)) {
                Db::delete(Table::ELEMENTS, [
                    'id' => array_keys($deletedElementIds),
                ]);
                Db::delete(Table::SEARCHINDEX, [
                    'elementId' => array_keys($deletedElementIds),
                ]);
            }
        }

        if (isset($this->successMessage)) {
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
