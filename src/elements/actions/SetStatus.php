<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\Element;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;

/**
 * SetStatus represents a Set Status element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
     * Performs the action on any elements that match the given criteria.
     *
     * @param ElementQueryInterface $query The element query defining which elements the action should affect.
     * @return bool Whether the action was performed successfully.
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $elementsService = Craft::$app->getElements();
        $enabled = ($this->status === Element::STATUS_ENABLED);

        /** @var Element[] $elements */
        $elements = $query->all();
        $failCount = 0;

        foreach ($elements as $element) {
            // Skip if there's nothing to change
            if ($element->enabled == $enabled && (!$enabled || $element->enabledForSite)) {
                continue;
            }

            if ($enabled) {
                // Also enable for this site
                $element->enabled = $element->enabledForSite = true;
                $element->setScenario(Element::SCENARIO_LIVE);
            } else {
                $element->enabled = false;
            }

            if ($elementsService->saveElement($element) === false) {
                // Validation error
                $failCount++;
            }
        }

        // Did all of them fail?
        if ($failCount === count($elements)) {
            if (count($elements) === 1) {
                $this->setMessage(Craft::t('app', 'Could not update status due to a validation error.'));
            } else {
                $this->setMessage(Craft::t('app', 'Could not update statuses due to validation errors.'));
            }

            return false;
        }

        if ($failCount !== 0) {
            $this->setMessage(Craft::t('app', 'Status updated, with some failures due to validation errors.'));
        } else {
            if (count($elements) === 1) {
                $this->setMessage(Craft::t('app', 'Status updated.'));
            } else {
                $this->setMessage(Craft::t('app', 'Statuses updated.'));
            }
        }

        return true;
    }
}
