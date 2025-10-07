<?php

namespace craft\elements\conditions\addresses;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\Address;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\conditions\HintableConditionRuleTrait;
use craft\elements\db\AddressQuery;
use craft\elements\db\ElementQueryInterface;
use CraftCms\Cms\Field\Addresses;
use CraftCms\Cms\Support\Facades\Fields;
use Illuminate\Support\Collection;

/**
 * Field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.0
 */
class FieldConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    use HintableConditionRuleTrait;

    /**
     * @inheritdoc
     */
    protected bool $includeEmptyOperators = true;

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Field');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['field', 'fieldId'];
    }

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return $this->addressFields()
            ->keyBy(fn(Addresses $field) => $field->uid)
            ->map(
                fn(Addresses $field) =>
                $field->getUiLabel() . ($this->showLabelHint() ? " ($field->handle)" : '')
            )
            ->all();
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AddressQuery $query */
        if ($this->operator === self::OPERATOR_NOT_EMPTY) {
            $query->field($this->addressFields()->all());
        } elseif ($this->operator === self::OPERATOR_EMPTY) {
            $query->field(false);
        } else {
            $query->fieldId($this->paramValue(fn($uid) => Fields::getFieldByUid($uid)->id ?? null));
        }
    }

    /**
     * @inheritdoc
     */
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
