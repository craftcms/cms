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
    // Constants
    // =========================================================================

    const ENABLED = 'enabled';
    const DISABLED_GLOBALLY = 'disabled';
    const DISABLED_FOR_SITE = 'disabled-for-site';

    // Properties
    // =========================================================================

    /**
     * @var bool Whether to show the “Disabled for Site” status option.
     */
    public $allowDisabledForSite = false;

    /**
     * @var string|null The status elements should be set to
     */
    public $status;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // Only allow the "Disabled for site" option if there are multiple sites and the element type is localized
        if ($this->allowDisabledForSite) {
            $this->allowDisabledForSite = $this->elementType && $this->elementType::isLocalized() && Craft::$app->getIsMultiSite();
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Set Status');
    }

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
            'range' => $this->allowDisabledForSite
                ? [self::ENABLED, self::DISABLED_GLOBALLY, self::DISABLED_FOR_SITE]
                : [self::ENABLED, self::DISABLED_GLOBALLY]
        ];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        return Craft::$app->getView()->renderTemplate('_components/elementactions/SetStatus/trigger', [
            'allowDisabledForSite' => $this->allowDisabledForSite,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $elementsService = Craft::$app->getElements();

        /** @var Element[] $elements */
        $elements = $query->all();
        $failCount = 0;

        foreach ($elements as $element) {
            switch ($this->status) {
                case self::ENABLED:
                    // Skip if there's nothing to change
                    if ($element->enabled && $element->enabledForSite) {
                        continue 2;
                    }

                    $element->enabled = $element->enabledForSite = true;
                    $element->setScenario(Element::SCENARIO_LIVE);
                    break;

                case self::DISABLED_GLOBALLY:
                    // Skip if there's nothing to change
                    if (!$element->enabled) {
                        continue 2;
                    }

                    $element->enabled = false;
                    break;

                case self::DISABLED_FOR_SITE:
                    // Skip if there's nothing to change
                    if (!$element->enabledForSite) {
                        continue 2;
                    }

                    $element->enabledForSite = false;
                    break;
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
