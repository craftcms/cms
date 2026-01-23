<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Validation\Concerns;

use CraftCms\Cms\Component\Validation\Attributes\Ruleset as RulesetAttribute;
use CraftCms\Cms\Component\Validation\Contracts\ValidatableComponentInterface;
use CraftCms\Cms\Component\Validation\Ruleset;
use CraftCms\Cms\Support\Arr;
use Illuminate\Validation\Validator;
use LogicException;
use ReflectionClass;
use Throwable;

/**
 * @mixin ValidatableComponentInterface
 */
trait ValidatesWithRuleset
{
    private ?Ruleset $ruleset = null;

    private ?Validator $validator = null;

    private bool $validated = false;

    public function setScenario($scenario): void
    {
        $this->getRuleset()->scenario = $scenario;
    }

    public function getScenario(): string
    {
        return $this->getRuleset()->scenario;
    }

    public function scenarios(): array
    {
        return $this->getRuleset()->scenarios();
    }

    public function getRuleset(): Ruleset
    {
        if (isset($this->ruleset)) {
            return $this->ruleset;
        }

        $attributes = new ReflectionClass($this)->getAttributes(RulesetAttribute::class);

        $class = match (true) {
            ! empty($attributes) => $attributes[0]->getArguments()[0],
            method_exists($this, 'rulesClass') => $this->rulesClass(),
            default => throw new LogicException(
                sprintf('Class %s must have the #[RulesClass] attribute or have a `rulesClass()` method when using %s', static::class, ValidatesWithRuleset::class)
            ),
        };

        return $this->ruleset = app()->make($class, ['component' => $this]);
    }

    protected function getValidator(?array $attributeNames = null, bool $fresh = false): Validator
    {
        if ($this->validated && ! $fresh) {
            return $this->validator;
        }

        $rules = $this->getRuleset()->rules();

        $data = [];

        foreach ($this->attributes() as $attribute) {
            try {
                $data[$attribute] = $this->$attribute;
            } catch (Throwable) {
                // Skip attributes that throw errors during access (e.g., lazy-loaded relations that fail)
                // This is expected for attributes that may not be accessible in all contexts
            }
        }

        return $this->validator = $this->validator
            ->setData(Arr::whereNotNull($data))
            ->setCustomMessages($this->getRuleset()->messages())
            ->setRules(is_null($attributeNames)
                ? $rules
                : Arr::only($rules, $attributeNames)
            );
    }

    /**
     * Validate the component.
     *
     * @param  array<string>  $attributeNames  Attributes to validate (empty array for all)
     * @param  bool  $clearErrors  Whether to clear existing errors first
     */
    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        $previousErrors = [];

        if (! $clearErrors && $this->validated) {
            $previousErrors = $this->getErrors();
        }

        $result = $this->getValidator($attributeNames, fresh: $clearErrors)
            ->after(function (Validator $validator) use ($previousErrors) {
                foreach ($previousErrors as $attribute => $messages) {
                    $validator->errors()->add($attribute, $messages);
                }
            })
            /** @phpstan-ignore-next-line */
            ->after(fn ($validator) => $this->afterValidate($validator))
            ->passes();

        $this->validated = true;

        return $result;
    }

    public function beforeValidate(): bool
    {
        return true;
    }

    public function afterValidate(/* Validator $validator */): void
    {
        // TODO: Event
        // if ($this instanceof Model) {
        //     $this->trigger(Model::EVENT_AFTER_VALIDATE);
        // }
    }

    /**
     * Check if the component has errors.
     */
    public function hasErrors($attribute = null): bool
    {
        if (! $this->validated) {
            return false;
        }

        if ($attribute === null) {
            return $this->getValidator()->fails();
        }

        return $this->getValidator()->errors()->has($attribute);
    }

    /**
     * Add errors to the validator.
     *
     * @param  array<string, string|array<string>>  $errors
     */
    public function addErrors(array $errors): void
    {
        foreach ($errors as $attribute => $messages) {
            if (is_array($messages)) {
                foreach ($messages as $message) {
                    $this->getValidator()->errors()->add($attribute, $message);
                }
            } else {
                $this->getValidator()->errors()->add($attribute, $messages);
            }
        }
    }

    /**
     * Add a single error.
     */
    public function addError($attribute, $error = ''): void
    {
        $this->getValidator()->errors()->add($attribute, $error);
    }

    /**
     * Clear errors.
     */
    public function clearErrors($attribute = null): void
    {
        if (! $this->validated && $this->validator === null) {
            return;
        }

        if ($attribute === null) {
            foreach (array_keys($this->getValidator()->errors()->getMessages()) as $key) {
                $this->getValidator()->errors()->forget($key);
            }

            return;
        }

        $this->getValidator()->errors()->forget($attribute);
    }

    /**
     * Returns the first error of every attribute.
     *
     * @return array<string, string>|array<string>
     *
     * @phpstan-ignore method.childReturnType
     */
    public function getErrors($attribute = null): array
    {
        if (! $this->validated && $this->validator === null) {
            return [];
        }

        if ($attribute === null) {
            return array_map(
                fn (array $messages) => $messages[0],
                $this->getValidator()->errors()->getMessages(),
            );
        }

        return $this->getValidator()->errors()->get($attribute);
    }

    /**
     * Get the first error for each attribute.
     *
     * @return array<string, string>
     */
    public function getFirstErrors(): array
    {
        return $this->getErrors();
    }

    /**
     * Get the first error for an attribute.
     */
    public function getFirstError($attribute): ?string
    {
        if (! $this->validated) {
            return null;
        }

        return $this->getValidator()->errors()->first($attribute) ?: null;
    }
}
