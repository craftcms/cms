<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Conditions;

use craft\base\ElementInterface;
use craft\helpers\Assets as AssetsHelper;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

use function CraftCms\Cms\t;

final class FileTypeConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return t('File Type');
    }

    /**
     * {@inheritdoc}
     */
    public function getExclusiveQueryParams(): array
    {
        return ['kind'];
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function operators(): array
    {
        return [
            self::OPERATOR_IN,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function options(): array
    {
        $options = [];
        foreach (AssetsHelper::getAllowedFileKinds() as $value => $kind) {
            $options[] = ['value' => $value, 'label' => $kind['label']];
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AssetQuery $query */
        $query->kind($this->paramValue());
    }

    /**
     * {@inheritdoc}
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Asset $element */
        return $this->matchValue($element->kind);
    }
}
