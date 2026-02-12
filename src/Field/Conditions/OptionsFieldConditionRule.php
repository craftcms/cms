<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions;

use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Field\BaseOptionsField;
use CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface;
use CraftCms\Cms\Field\Data\MultiOptionsFieldData;
use CraftCms\Cms\Field\Data\OptionData;
use CraftCms\Cms\Field\Data\SingleOptionFieldData;
use Illuminate\Support\Collection;
use yii\base\InvalidConfigException;

class OptionsFieldConditionRule extends BaseMultiSelectConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    protected bool $includeEmptyOperators = true;

    protected function options(): array
    {
        /** @var BaseOptionsField $field */
        $field = $this->field();

        return Collection::make($field->options)
            ->filter(fn (array $option) => (array_key_exists('value', $option) &&
                $option['value'] !== null &&
                $option['value'] !== '' &&
                $option['label'] !== null &&
                $option['label'] !== ''
            ))
            ->map(fn (array $option) => [
                'value' => $option['value'],
                'label' => $option['label'],
            ])
            ->all();
    }

    #[\Override]
    protected function inputHtml(): string
    {
        if (! $this->field() instanceof BaseOptionsField) {
            throw new InvalidConfigException;
        }

        return parent::inputHtml();
    }

    protected function elementQueryParam(): string|array|null
    {
        if (! $this->field() instanceof BaseOptionsField) {
            return null;
        }

        return $this->paramValue();
    }

    protected function matchFieldValue($value): bool
    {
        if (! $this->field() instanceof BaseOptionsField) {
            return true;
        }

        if ($value instanceof MultiOptionsFieldData) {
            $value = array_map(fn (OptionData $option) => $option->value, (array) $value);
        } elseif ($value instanceof SingleOptionFieldData) {
            $value = $value->value;
        }

        return $this->matchValue($value);
    }
}
