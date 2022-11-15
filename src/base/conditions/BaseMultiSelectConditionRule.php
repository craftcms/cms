<?php

namespace craft\base\conditions;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Html;
use yii\base\InvalidConfigException;

/**
 * BaseMultiSelectConditionRule provides a base implementation for condition rules that are composed of a multi-select input.
 *
 * @property string[] $values
 * @since 4.0.0
 */
abstract class BaseMultiSelectConditionRule extends BaseConditionRule
{
    /**
     * @inheritdoc
     */
    public string $operator = self::OPERATOR_IN;

    /**
     * @var string[]
     */
    private array $_values = [];

    /**
     * Returns the operators that should be allowed for this rule.
     *
     * @return array
     */
    protected function operators(): array
    {
        return [
            self::OPERATOR_IN,
            self::OPERATOR_NOT_IN,
        ];
    }

    /**
     * @return string[]
     */
    public function getValues(): array
    {
        return $this->_values;
    }

    /**
     * @param string|string[] $values
     */
    public function setValues(array|string $values): void
    {
        if ($values === '') {
            $this->_values = [];
        } else {
            $this->_values = ArrayHelper::toArray($values);
        }
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'values' => $this->_values,
        ]);
    }

    /**
     * Defines the selectable options.
     *
     * Options can be expressed as value/label pairs, or as arrays with `value` and `label` keys.
     *
     * @return string[]|array[]
     * @phpstan-return string[]|array{value:string,label:string}[]
     */
    abstract protected function options(): array;

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        $multiSelectId = 'multiselect';
        $namespacedId = Craft::$app->getView()->namespaceInputId($multiSelectId);

        $js = <<<JS
$('#$namespacedId').selectize({
    plugins: ['remove_button'],
    onDropdownClose: () => {
        htmx.trigger(htmx.find('#$namespacedId'), 'change');
    },
});
JS;
        Craft::$app->getView()->registerJs($js);

        return
            Html::hiddenLabel(Html::encode($this->getLabel()), $multiSelectId) .
            Cp::multiSelectHtml([
                'id' => $multiSelectId,
                'class' => 'selectize flex-grow',
                'name' => 'values',
                'values' => $this->_values,
                'options' => $this->options(),
                'inputAttributes' => [
                    'style' => [
                        'display' => 'none', // Hide it before selectize does its thing
                    ],
                ],
            ]);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['values'], 'safe'],
        ]);
    }

    /**
     * Returns the rule’s value, prepped for [[Db::parseParam()]] based on the selected operator.
     *
     * @param callable|null $normalizeValue Method for normalizing a given selected value.
     * @return array|null
     */
    protected function paramValue(?callable $normalizeValue = null): ?array
    {
        $values = [];
        foreach ($this->_values as $value) {
            if ($normalizeValue !== null) {
                $value = $normalizeValue($value);
                if ($value === null) {
                    continue;
                }
            }
            $values[] = Db::escapeParam($value);
        }

        if (!$values) {
            return null;
        }

        return match ($this->operator) {
            self::OPERATOR_IN => $values,
            self::OPERATOR_NOT_IN => array_merge(['not'], $values),
            default => throw new InvalidConfigException("Invalid operator: $this->operator"),
        };
    }

    /**
     * Returns whether the condition rule matches the given value.
     *
     * @param string|string[]|null $value
     * @return bool
     */
    protected function matchValue(array|string|null $value): bool
    {
        if (!$this->_values) {
            return true;
        }

        if ($value === '' || $value === null) {
            $value = [];
        } else {
            $value = (array)$value;
        }

        return match ($this->operator) {
            self::OPERATOR_IN => !empty(array_intersect($value, $this->_values)),
            self::OPERATOR_NOT_IN => empty(array_intersect($value, $this->_values)),
            default => throw new InvalidConfigException("Invalid operator: $this->operator"),
        };
    }
}
