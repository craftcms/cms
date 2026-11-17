<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Conditions;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\ImageOrientation;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use Illuminate\Database\Query\Builder;
use Override;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class OrientationConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public static function isSelectableForCondition(ConditionInterface $condition): bool
    {
        return $condition instanceof AssetCondition;
    }

    public function getLabel(): string
    {
        return t('Orientation');
    }

    /** @return list<string> */
    #[Override]
    protected function operators(): array
    {
        return [
            self::OPERATOR_IN,
            self::OPERATOR_NOT_IN,
        ];
    }

    protected function options(): array
    {
        return array_map(
            static fn (ImageOrientation $case): array => ['value' => $case->value, 'label' => $case->label()],
            ImageOrientation::cases(),
        );
    }

    public function modifyQuery(Builder $query, ElementQueryInterface $elementQuery): void
    {
        AssetQuery::applyOrientation($query, $this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Asset $element */
        return $this->matchValue($element->getOrientation()?->value);
    }
}
