<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Conditions;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Condition\BaseTextConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\ElementQuery;
use Illuminate\Contracts\Database\Query\Builder;

use function CraftCms\Cms\t;

class FilenameConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Filename');
    }

    public function modifyQuery(Builder $query, ElementQuery $elementQuery): void
    {
        AssetQuery::applyFilename($query, $this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Asset $element */
        return $this->matchValue($element->getFilename());
    }
}
