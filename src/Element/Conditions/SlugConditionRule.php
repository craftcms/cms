<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use craft\base\ElementInterface;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Condition\BaseTextConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

use function CraftCms\Cms\t;

class SlugConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return t('Slug');
    }

    /**
     * {@inheritdoc}
     */
    public function getExclusiveQueryParams(): array
    {
        return ['slug'];
    }

    /**
     * {@inheritdoc}
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->slug($this->paramValue());
    }

    /**
     * {@inheritdoc}
     */
    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->slug);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function isEmpty(mixed $value): bool
    {
        if (parent::isEmpty($value)) {
            return true;
        }

        return is_string($value) && ElementHelper::isTempSlug($value);
    }
}
