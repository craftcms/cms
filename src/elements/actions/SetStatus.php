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
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;

/**
 * SetStatus represents a Set Status element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SetStatus extends ElementAction
{
    public const ENABLED = 'enabled';
    /**
     * @since 3.4.0
     */
    public const DISABLED = 'disabled';

    /**
     * @var string|null The status elements should be set to
     */
    public ?string $status = null;

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
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['status'], 'required'];
        $rules[] = [['status'], 'in', 'range' => [self::ENABLED, self::DISABLED]];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        validateSelection: \$selectedItems => Garnish.hasAttr(\$selectedItems.find('.element'), 'data-savable'),
    });
})();
JS, [static::class]);

        return Craft::$app->getView()->renderTemplate('_components/elementactions/SetStatus/trigger.twig');
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        /** @var ElementInterface $elementType */
        $elementType = $this->elementType;
        $isLocalized = $elementType::isLocalized() && Craft::$app->getIsMultiSite();
        $elementsService = Craft::$app->getElements();

        $elements = $query->all();
        $failCount = 0;

        foreach ($elements as $element) {
            switch ($this->status) {
                case self::ENABLED:
                    // Skip if there's nothing to change
                    if ($element->enabled && $element->getEnabledForSite()) {
                        continue 2;
                    }

                    $element->enabled = true;
                    $element->setEnabledForSite(true);
                    $element->setScenario(Element::SCENARIO_LIVE);
                    break;

                case self::DISABLED:
                    // Is this a multi-site element?
                    if ($isLocalized && count($element->getSupportedSites()) !== 1) {
                        // Skip if there's nothing to change
                        if (!$element->getEnabledForSite()) {
                            continue 2;
                        }
                        $element->setEnabledForSite(false);
                    } else {
                        // Skip if there's nothing to change
                        if (!$element->enabled) {
                            continue 2;
                        }
                        $element->enabled = false;
                    }
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
