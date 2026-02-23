<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Conditions;

use craft\base\ElementInterface;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\HintableConditionRuleTrait;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Field\Addresses;
use CraftCms\Cms\Support\Facades\Fields;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;

final class FieldConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    use HintableConditionRuleTrait;

    #[\Override]
    protected bool $includeEmptyOperators = true;

    public function getLabel(): string
    {
        return t('Field');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['field', 'fieldId'];
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

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AddressQuery $query */
        if ($this->operator === self::OPERATOR_NOT_EMPTY) {
            $query->field($this->addressFields()->all());
        } elseif ($this->operator === self::OPERATOR_EMPTY) {
            $query->field(false);
        } else {
            $query->fieldId($this->paramValue(fn ($uid) => Fields::getFieldByUid($uid)->id ?? null));
        }
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
     * @return Collection<Addresses>
     */
    private function addressFields(): Collection
    {
        return Fields::getFieldsByType(Addresses::class);
    }
}
