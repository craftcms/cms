<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use CraftCms\Cms\Condition\BaseTextConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use Illuminate\Database\Query\Builder;

use function CraftCms\Cms\t;

class SlugConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Slug');
    }

    public function modifyQuery(Builder $query, ElementQueryInterface $elementQuery): void
    {
        ElementQuery::applySlug($query, $this->paramValue());
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
