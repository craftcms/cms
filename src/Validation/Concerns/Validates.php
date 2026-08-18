<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Concerns;

use CraftCms\Cms\Component\Exceptions\InvalidCallException;
use CraftCms\Cms\Component\Exceptions\UnknownPropertyException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Support\Utils;
use CraftCms\Cms\Validation\Contracts\Validatable;
use CraftCms\Cms\Validation\ValidatableRules;
use CraftCms\RulesetValidation\Concerns\HasRuleset;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Validator;

trait Validates
{
    use HasRuleset;

    private ?MessageBag $_errors = null;

    public private(set) MessageBag $errors {
        get => $this->errors();
        set(MessageBag $value) {
            $this->_errors = $value;
        }
    }

    public function getFirstErrors(): array
    {
        return array_map(fn (array $messages) => Arr::first($messages), $this->errors()->getMessages());
    }

    public function errors(): MessageBag
    {
        if (isset($this->_errors)) {
            return $this->_errors;
        }

        $this->_errors = new MessageBag;
        $sessionErrors = session('errors', new MessageBag);
        $this->_errors->merge(
            $sessionErrors instanceof MessageBag
                ? $sessionErrors->getMessages()
                : (is_array($sessionErrors) ? $sessionErrors : [])
        );

        return $this->_errors;
    }

    public function clearErrors(?string $attribute = null): void
    {
        if ($attribute) {
            $this->errors()->forget($attribute);

            return;
        }

        $this->_errors = new MessageBag;
    }

    public function addModelErrors(Validatable $model, string $attrPrefix = ''): void
    {
        if ($attrPrefix !== '') {
            $attrPrefix = rtrim($attrPrefix, '.').'.';
        }

        foreach ($model->errors()->getMessages() as $attribute => $errors) {
            foreach ($errors as $error) {
                $this->errors()->add($attrPrefix.$attribute, $error);
            }
        }
    }

    public function validate(array|string|null $attributeNames = null, bool $clearErrors = true, bool $throw = false): bool
    {
        if ($clearErrors) {
            $this->clearErrors();
        }

        if (is_string($attributeNames)) {
            $attributeNames = [$attributeNames];
        }

        $ruleset = $this->ruleset ?: app()->make(ValidatableRules::class, ['subject' => $this]);

        if (! is_null($attributeNames)) {
            $ruleset->only($attributeNames);
        }

        if ($throw) {
            $ruleset->validate();
            $result = true;
        } else {
            $result = $ruleset->passes();
        }

        $this->errors()->merge($ruleset->getValidator()->errors());

        return $result && $this->errors()->isEmpty();
    }

    public function getRules(): array
    {
        return [];
    }

    public function getMessages(): array
    {
        return [];
    }

    public function attributeLabels(): array
    {
        return [];
    }

    public function getAttributeLabel(string $attribute): string
    {
        $labels = $this->attributeLabels();

        return $labels[$attribute] ?? $this->generateAttributeLabel($attribute);
    }

    public function setAttributes($values): void
    {
        Typecast::properties(static::class, $values);

        foreach ($values as $name => $value) {
            try {
                $this->$name = $value;
            } catch (UnknownPropertyException|InvalidCallException) {
                // Property or setter doesn't exist
            }
        }
    }

    /**
     * Generates a user friendly attribute label based on the give attribute name.
     * This is done by replacing underscores, dashes and dots with blanks and
     * changing the first letter of each word to upper case.
     * For example, 'department_name' or 'DepartmentName' will generate 'Department Name'.
     */
    public function generateAttributeLabel(string $name): string
    {
        return Str::camel2words($name);
    }

    public function prepareForValidation(): void {}

    public function passedValidation(): void {}

    public function afterValidate(?Validator $validator = null): void {}

    public function validationData(): array
    {
        return Arr::except(Utils::getPublicProperties($this), ['ruleset']);
    }
}
