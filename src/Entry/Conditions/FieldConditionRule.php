<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Conditions;

use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\HintableConditionRuleTrait;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Fields;
use Illuminate\Support\Collection;
use Override;

use function CraftCms\Cms\t;

/**
 * Field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 5.6.0
 */
class FieldConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    use HintableConditionRuleTrait;

    #[Override]
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
        return $this->nestedEntryFields()
            ->keyBy(fn (ElementContainerFieldInterface $field) => $field->uid)
            ->map(
                fn (ElementContainerFieldInterface $field) => $field->getUiLabel().($this->showLabelHint() ? " ($field->handle)" : '')
            )
            ->all();
    }

    /** @param EntryQuery<Entry> $query */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        if ($this->operator === self::OPERATOR_NOT_EMPTY) {
            $query->field($this->nestedEntryFields()->all());
        } elseif ($this->operator === self::OPERATOR_EMPTY) {
            $query->field(false);
        } else {
            $fieldsService = app(Fields::class);
            $query->fieldId($this->paramValue(fn ($uid) => $fieldsService->getFieldByUid($uid)->id ?? null));
        }
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Entry $element */
        return match ($this->operator) {
            self::OPERATOR_NOT_EMPTY => $element->getField() !== null,
            self::OPERATOR_EMPTY => $element->getField() === null,
            default => $this->matchValue($element->getField()?->uid),
        };
    }

    /**
     * @return Collection<int, ElementContainerFieldInterface>
     */
    private function nestedEntryFields(): Collection
    {
        $fieldsService = app(Fields::class);

        return $fieldsService->getNestedEntryFieldTypes()
            ->map(fn (string $class) => $fieldsService->getFieldsByType($class))
            ->flatten(1);
    }
}
