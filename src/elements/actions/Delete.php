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

/**
 * Delete represents a Delete element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Delete extends ElementAction
{
    /**
     * @var bool Whether to delete the element’s descendants as well.
     * @since 3.5.0
     */
    public $withDescendants = false;

    /**
     * @var bool Whether to permanently delete the elements.
     * @since 3.5.0
     */
    public $hard = false;

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
     * @since 3.5.0
     */
    public function getTriggerHtml()
    {
        if ($this->hard) {
            return '<div class="btn formsubmit">' . $this->getTriggerLabel() . '</div>';
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
    public function getConfirmationMessage()
    {
        if ($this->confirmationMessage !== null) {
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
        if ($this->hard) {
            $ids = $query->ids();
            if (!empty($ids)) {
                Db::delete(Table::ELEMENTS, [
                    'id' => $ids,
                ]);
            }
        } else {
            $elementsService = Craft::$app->getElements();

            if ($this->withDescendants) {
                $query
                    ->with([
                        [
                            'descendants',
                            [
                                'orderBy' => ['structureelements.lft' => SORT_DESC],
                            ]
                        ]
                    ])
                    ->orderBy(['structureelements.lft' => SORT_DESC]);
            }

            $deletedElementIds = [];

            foreach ($query->all() as $element) {
                if (!isset($deletedElementIds[$element->id])) {
                    if ($this->withDescendants) {
                        foreach ($element->getDescendants() as $descendant) {
                            if (!isset($deletedElementIds[$descendant->id])) {
                                $elementsService->deleteElement($descendant);
                                $deletedElementIds[$descendant->id] = true;
                            }
                        }
                    }
                    $elementsService->deleteElement($element);
                    $deletedElementIds[$element->id] = true;
                }
            }
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
