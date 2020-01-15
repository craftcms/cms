<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;
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
    public $successMessage;

    /**
     * @var string|null The message that should be shown after some elements get restored
     */
    public $partialSuccessMessage;

    /**
     * @var string|null The message that should be shown if no elements get restored
     */
    public $failMessage;

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
    public function getTriggerHtml()
    {
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
