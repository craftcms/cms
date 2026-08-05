<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Conditions;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use Override;

use function CraftCms\Cms\t;

class FileTypeConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('File Type');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['kind'];
    }

    /** @return list<string> */
    #[Override]
    protected function operators(): array
    {
        return [
            self::OPERATOR_IN,
        ];
    }

    protected function options(): array
    {
        $options = [];
        foreach (AssetsHelper::getAllowedFileKinds() as $value => $kind) {
            $options[] = ['value' => $value, 'label' => $kind['label']];
        }

        return $options;
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AssetQuery $query */
        $query->kind($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Asset $element */
        return $this->matchValue($element->kind);
    }
}
