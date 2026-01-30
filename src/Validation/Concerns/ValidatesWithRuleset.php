<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Concerns;

use BadMethodCallException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Validation\Attributes\Ruleset as RulesetAttribute;
use CraftCms\Cms\Validation\Contracts\Validatable;
use CraftCms\Cms\Validation\Ruleset;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;
use ReflectionClass;

/**
 * @mixin Validatable
 */
trait ValidatesWithRuleset
{
    use HasScenarios;
    use InteractsWithValidator;

    private ?Ruleset $ruleset = null;

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

        $ruleset = $this->getRuleset();

        return $this->validator
            ->setData($this->getAttributes())
            ->setCustomMessages($ruleset->messages())
            ->setAttributeNames($ruleset->attributes())
            ->setRules(is_null($attributeNames)
                ? $ruleset->rules()
                : Arr::only($ruleset->rules(), $attributeNames)
            );
    }
}
