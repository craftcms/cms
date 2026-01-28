<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Concerns;

use BadMethodCallException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Validation\Attributes\Ruleset as RulesetAttribute;
use CraftCms\Cms\Validation\Contracts\Validatable;
use CraftCms\Cms\Validation\Ruleset;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Validator;
use ReflectionClass;

/**
 * @mixin Validatable
 */
trait ValidatesWithRuleset
{
    use HasScenarios;

    private ?Ruleset $ruleset = null;

    private ?Validator $validator = null;

    public function getRuleset(): Ruleset
    {
        if (isset($this->ruleset)) {
            return $this->ruleset;
        }

        $attributes = new ReflectionClass($this)->getAttributes(RulesetAttribute::class);

        try {
            $class = isset($attributes[0])
                ? $attributes[0]->getArguments()[0]
                : $this->rulesClass();
        } catch (BadMethodCallException) {
            throw new BadMethodCallException(
                sprintf('Class %s must have the #[RulesClass] attribute or have a `rulesClass()` method when using %s', static::class, ValidatesWithRuleset::class)
            );
        }

        return $this->ruleset = app()->make($class, ['component' => $this]);
    }

    protected function getValidator(?array $attributeNames = null, bool $fresh = false): Validator
    {
        if ($fresh || ! isset($this->validator)) {
            $this->validator = ValidatorFacade::make([], []);
        }

        $rules = $this->getRuleset()->rules();

        return $this->validator
            ->setData($this->getAttributes())
            ->setCustomMessages($this->getRuleset()->messages())
            ->setRules(is_null($attributeNames)
                ? $rules
                : Arr::only($rules, $attributeNames)
            );
    }

    public function errors(): MessageBag
    {
        return $this->getValidator()->errors();
    }

    /**
     * Validate the component.
     *
     * @param  array<string>|null  $attributeNames  Attributes to validate (null for all)
     * @param  bool  $clearErrors  Whether to clear existing errors first
     */
    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        $previousErrors = ! $clearErrors && isset($this->validator)
            ? $this->errors()->getMessages()
            : [];

        $this->getRuleset()->prepareForValidation($attributeNames);

        $result = $this->getValidator($attributeNames, fresh: $clearErrors)
            /** @phpstan-ignore-next-line */
            ->after(fn ($validator) => $this->afterValidate($validator))
            ->passes();

        $this->errors()->merge($previousErrors);

        return $result && $this->errors()->isEmpty();
    }

    public function beforeValidate(): bool
    {
        return true;
    }

    public function afterValidate(/* Validator $validator */): void {}

    /**
     * {@inheritDoc}
     */
    public function getFirstErrors(): array
    {
        return array_map(fn (array $messages) => Arr::first($messages), $this->errors()->getMessages());
    }
}
