<?php

namespace craft\elements\conditions;

use craft\base\conditions\BaseNumberConditionRule;
use craft\base\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use function CraftCms\Cms\t;

/**
 * ID condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class IdConditionRule extends BaseNumberConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return t('ID');
    }

    public static function supportsProjectConfig(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['id'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->id($this->paramValue());
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->id);
    }
}
