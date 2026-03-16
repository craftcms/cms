<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Closure;
use Craft;
use CraftCms\Cms\Element\Element;
use Illuminate\Contracts\Database\Query\Builder;
use yii\base\InvalidConfigException;
use yii\validators\Validator;

/**
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Field} instead.
 */
abstract class Field extends \CraftCms\Cms\Field\Field
{
    use \craft\base\LegacyEventConstants;

    public static function modifyQuery(Builder $query, array $instances, mixed $value): Builder
    {
        if (!method_exists(static::class, 'queryCondition')) {
            return $query;
        }

        $params = [];

        $condition = static::queryCondition($instances, $value, $params);

        if ($condition === null || $condition === false) {
            return $query;
        }

        $db = Craft::$app->getDb();
        $sql = $db->getQueryBuilder()->buildCondition($condition, $params);

        // Yii uses named parameters, Laravel uses positional
        $sql = preg_replace('/:qp\d+/', '?', $sql);

        return $query->whereRaw($sql, array_values($params));
    }

    public function getElementValidationRules(): array
    {
        return [];
    }

    public function getElementRules(ElementInterface $element): array
    {
        return [
            function(string $attribute, mixed $value, Closure $fail) use ($element) {
                $scenario = $element->getScenario();
                $isEmpty = fn() => $this->isValueEmpty($element->getFieldValue($this->handle), $element);

                foreach ($this->getElementValidationRules() as $rule) {
                    $validator = $this->_normalizeFieldValidator($attribute, $rule, $element, $isEmpty);

                    if (
                        in_array($element->getScenario(), $validator->on) ||
                        (empty($validator->on) && !in_array($scenario, $validator->except))
                    ) {
                        $validator->validateAttributes($element);
                    }
                }
            },
        ];
    }

    /**
     * Normalizes a field’s validation rule.
     *
     *
     * @throws InvalidConfigException
     */
    private function _normalizeFieldValidator(
        string $attribute,
        mixed $rule,
        ElementInterface $element,
        callable $isEmpty,
    ): Validator {
        if ($rule instanceof Validator) {
            return $rule;
        }

        if (is_string($rule)) {
            // "Validator" syntax
            $rule = [$attribute, $rule, 'on' => [Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE]];
        }

        if (!is_array($rule) || !isset($rule[0])) {
            throw new InvalidConfigException('Invalid validation rule for custom field "' . $this->handle . '".');
        }

        if (isset($rule[1])) {
            // Make sure the attribute name starts with 'field:'
            if ($rule[0] === $this->handle) {
                $rule[0] = $attribute;
            }
        } else {
            // ["Validator"] syntax
            array_unshift($rule, $attribute);
        }

        if (
            (!is_string($rule[1]) || !isset(Validator::$builtInValidators[$rule[1]])) &&
            (is_callable($rule[1]) || method_exists($this, $rule[1]))
        ) {
            // InlineValidator assumes that the closure is on the model being validated
            // so it won’t pass a reference to the element
            $rule['params'] = [
                $this,
                $rule[1],
                $rule['params'] ?? null,
            ];
            $rule[1] = 'validateCustomFieldAttribute';
        }

        // Set 'isEmpty' to the field's isEmpty() method by default
        if (!array_key_exists('isEmpty', $rule)) {
            $rule['isEmpty'] = $isEmpty;
        }

        // Set 'on' to the main scenarios by default
        if (!array_key_exists('on', $rule)) {
            $rule['on'] = [Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE];
        }

        return Validator::createValidator($rule[1], $element, (array) $rule[0], array_slice($rule, 2));
    }

    /**
     * Calls a custom validation function on a custom field.
     *
     * This will be called by [[\yii\validators\InlineValidator]] if a custom field specified
     * a closure or the name of a class-level method as the validation type.
     *
     * @param  string  $attribute  The field handle
     */
    public function validateCustomFieldAttribute(string $attribute, ?array $params = null): void
    {
        /** @var array|null $params */
        [$field, $method, $fieldParams] = $params;

        if (is_string($method) && !is_callable($method)) {
            $method = [$field, $method];
        }

        $method($this, $fieldParams);
    }
}
