<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\elements\actions;

use Craft;
use craft\base\Element;
use craft\base\ElementAction;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\events\SetStatusEvent;

/**
 * SetStatus represents a Set Status element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SetStatus extends ElementAction
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The status elements should be set to
     */
    public $status;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Set Status');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['status'], 'required'];
        $rules[] = [
            ['status'],
            'in',
            'range' => [Element::STATUS_ENABLED, Element::STATUS_DISABLED]
        ];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        return Craft::$app->getView()->renderTemplate('_components/elementactions/SetStatus/trigger');
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $elementsService = Craft::$app->getElements();
        $enabled = ($this->status === Element::STATUS_ENABLED);

        /** @var Element[] $elements */
        $elements = $query->all();
        $someFailed = false;

        foreach ($elements as $element) {
            // Skip if there's nothing to change
            if ($element->enabled == $enabled && (!$enabled || $element->enabledForSite)) {
                continue;
            }

            if ($enabled) {
                // Also enable for this site
                $element->enabled = $element->enabledForSite = true;
            } else {
                $element->enabled = false;
            }

            if ($elementsService->saveElement($element) === false) {
                // Validation error
                $someFailed = true;
            }
        }

        if ($someFailed === true) {
            if (count($elements) === 1) {
                $this->setMessage(Craft::t('app', 'Could not update status due to a validation error.'));
            } else {
                $this->setMessage(Craft::t('app', 'Could not update some statuses due to validation errors.'));
            }

            return false;
        }

        if (count($elements) === 1) {
            $this->setMessage(Craft::t('app', 'Status updated.'));
        } else {
            $this->setMessage(Craft::t('app', 'Statuses updated.'));
        }

        return true;
    }
}
