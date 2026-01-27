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
 * Width condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class WidthConditionRule extends BaseNumberConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return t('Width');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['width'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AssetQuery $query */
        $query->width($this->paramValue());
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Asset $element */
        return $this->matchValue($element->getWidth());
    }
}
