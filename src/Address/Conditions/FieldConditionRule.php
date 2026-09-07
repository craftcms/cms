<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Conditions;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\HintableConditionRuleTrait;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Field\Addresses;
use CraftCms\Cms\Field\Fields as FieldsService;
use CraftCms\Cms\Support\Facades\Fields;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;

class FieldConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    use HintableConditionRuleTrait;

    #[\Override]
    protected bool $includeEmptyOperators = true;

    public function getLabel(): string
    {
        return t('Field');
    }

    protected function options(): array
    {
        return $this->addressFields()
            ->keyBy(fn (Addresses $field) => $field->uid)
            ->map(
                fn (Addresses $field) => $field->getUiLabel().($this->showLabelHint() ? " ($field->handle)" : '')
            )
            ->all();
    }

    public function modifyQuery(Builder $query, ElementQuery $elementQuery): void
    {
        if ($this->operator === self::OPERATOR_NOT_EMPTY) {
            $fieldIds = $this->addressFields()->pluck('id');
        } elseif ($this->operator === self::OPERATOR_EMPTY) {
            $fieldIds = false;
        } else {
            $fieldIds = $this->paramValue(fn ($uid) => Fields::getFieldByUid($uid)->id ?? null);
        }

        AddressQuery::applyFieldId($query, $fieldIds, $elementQuery);
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Address $element */
        return match ($this->operator) {
            self::OPERATOR_NOT_EMPTY => $element->getField() !== null,
            self::OPERATOR_EMPTY => $element->getField() === null,
            default => $this->matchValue($element->getField()?->uid),
        };
    }

    /**
     * @return Collection<int, Addresses>
     */
    private function addressFields(): Collection
    {
        return app(FieldsService::class)->getFieldsByType(Addresses::class);
    }
}
