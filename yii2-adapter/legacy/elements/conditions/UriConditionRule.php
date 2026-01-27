<?php

namespace craft\elements\conditions;

use craft\base\conditions\BaseTextConditionRule;
use craft\base\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use function CraftCms\Cms\t;

/**
 * ID condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class UriConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return t('URI');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['uri'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->uri($this->paramValue());
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->uri);
    }
}
