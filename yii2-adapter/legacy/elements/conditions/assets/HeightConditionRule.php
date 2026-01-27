<?php

namespace craft\elements\conditions\assets;

use craft\base\conditions\BaseNumberConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\AssetQuery;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use function CraftCms\Cms\t;

/**
 * Height condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class HeightConditionRule extends BaseNumberConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return t('Height');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['height'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AssetQuery $query */
        $query->height($this->paramValue());
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Asset $element */
        return $this->matchValue($element->getHeight());
    }
}
