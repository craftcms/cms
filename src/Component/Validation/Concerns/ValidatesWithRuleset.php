<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Validation\Concerns;

use CraftCms\Cms\Component\Validation\Attributes\Ruleset as RulesetAttribute;
use CraftCms\Cms\Component\Validation\Contracts\ValidatableComponentInterface;
use CraftCms\Cms\Component\Validation\Ruleset;
use CraftCms\Cms\Support\Arr;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Validator;
use LogicException;
use ReflectionClass;

/**
 * @mixin ValidatableComponentInterface
 */
trait ValidatesWithRuleset
{
    private ?Ruleset $ruleset = null;

    private ?Validator $validator = null;

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
        if ($fresh || ! isset($this->validator)) {
            $this->validator = ValidatorFacade::make([], []);
        }

        $rules = $this->getRuleset()->rules();

        return $this->validator
            ->setData(Arr::whereNotNull($this->getAttributes()))
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
     * @param  array<string>  $attributeNames  Attributes to validate (empty array for all)
     * @param  bool  $clearErrors  Whether to clear existing errors first
     */
    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        $previousErrors = ! $clearErrors && isset($this->validator)
            ? $this->errors()->getMessages()
            : [];

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

    public function afterValidate(Validator $validator): void
    {
        // TODO: Event
        // if ($this instanceof Model) {
        //     $this->trigger(Model::EVENT_AFTER_VALIDATE);
        // }
    }

    /**
     * {@inheritDoc}
     */
    public function getFirstErrors(): array
    {
        return array_map(fn (array $messages) => Arr::first($messages), $this->errors()->getMessages());
    }
}
