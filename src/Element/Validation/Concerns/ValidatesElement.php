<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Validation\Concerns;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Validation\Concerns\ValidatesWithRuleset;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use Illuminate\Support\Facades\Request;
use yii\base\InvalidConfigException;
use yii\validators\RequiredValidator;
use yii\validators\Validator as YiiValidator;

/**
 * Trait for Element validation.
 */
trait ValidatesElement
{
    use ValidatesWithRuleset;

    /**
     * {@inheritdoc}
     */
    public function afterValidate(/* Validator $validator */): void
    {
        if (
            Cms::isInstalled() &&
            $fieldLayout = $this->getFieldLayout()
        ) {
            $scenario = $this->getScenario();
            $layoutElements = $fieldLayout->getVisibleCustomFieldElements($this);

            foreach ($layoutElements as $layoutElement) {
                $field = $layoutElement->getField();
                $attribute = "field:$field->handle";

                if (isset($this->_attributeNames) && ! isset($this->_attributeNames[$attribute])) {
                    continue;
                }

                $isEmpty = fn () => $field->isValueEmpty($this->getFieldValue($field->handle), $this);

                if ($scenario === Element::SCENARIO_LIVE && $layoutElement->required) {
                    new RequiredValidator(['isEmpty' => $isEmpty])
                        ->validateAttribute($this, $attribute);
                }

                foreach ($field->getElementValidationRules() as $rule) {
                    /** @var YiiValidator $yValidator */
                    $yValidator = $this->_normalizeFieldValidator($attribute, $rule, $field, $isEmpty);
                    if (
                        in_array($scenario, $yValidator->on) ||
                        (empty($yValidator->on) && ! in_array($scenario, $yValidator->except))
                    ) {
                        $yValidator->validateAttributes($this);
                    }
                }
            }
        }

        if (Request::isCpRequest()) {
            foreach ($this->errors()->getMessages() as $attribute => $errors) {
                $label = $this->getAttributeLabel($attribute);

                foreach ($errors as $error) {
                    $this->errors()->forget($attribute);
                    $this->errors()->add($attribute, str_replace($label, "*$label*", $error));
                }
            }
        }
    }

    /**
     * Normalizes a field’s validation rule.
     *
     * @throws InvalidConfigException
     */
    private function _normalizeFieldValidator(
        string $attribute,
        mixed $rule,
        FieldInterface $field,
        callable $isEmpty,
    ): YiiValidator {
        if ($rule instanceof YiiValidator) {
            return $rule;
        }

        if (is_array($rule)) {
            $type = array_shift($rule);
            if (isset($rule['on'])) {
                $on = (array) $rule['on'];
                unset($rule['on']);
            }
            if (isset($rule['except'])) {
                $except = (array) $rule['except'];
                unset($rule['except']);
            }
        } else {
            $type = $rule;
            $rule = [];
        }

        $validator = YiiValidator::createValidator($type, $this, $attribute, $rule);
        $validator->on = $on ?? [];
        $validator->except = $except ?? [];
        $validator->isEmpty = $isEmpty;

        return $validator;
    }
}
