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
    public function getLabel(): string
    {
        return t('Slug');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['slug'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->slug($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->slug);
    }

    #[\Override]
    protected function isEmpty(mixed $value): bool
    {
        if (parent::isEmpty($value)) {
            return true;
        }

        return is_string($value) && ElementHelper::isTempSlug($value);
    }
}
