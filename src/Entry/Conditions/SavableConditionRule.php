<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Conditions;

use Craft;
use craft\base\ElementInterface;
use craft\elements\db\EntryQuery;
use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

use function CraftCms\Cms\t;

/**
 * Entry savable condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.4.0
 */
class SavableConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return t('Savable');
    }

    /**
     * {@inheritdoc}
     */
    public function getExclusiveQueryParams(): array
    {
        return ['savable'];
    }

    /**
     * {@inheritdoc}
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var EntryQuery $query */
        $query->savable($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function matchElement(ElementInterface $element): bool
    {
        $savable = Craft::$app->getElements()->canSave($element);

        return $savable === $this->value;
    }
}
