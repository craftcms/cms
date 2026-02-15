<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Conditions;

use craft\base\ElementInterface;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Condition\BaseTextConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

use function CraftCms\Cms\t;

final class FilenameConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Filename');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['filename'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AssetQuery $query */
        $query->filename($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Asset $element */
        return $this->matchValue($element->getFilename());
    }
}
