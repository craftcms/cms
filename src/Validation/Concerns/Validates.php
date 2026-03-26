<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Concerns;

use BadMethodCallException;
use CraftCms\Cms\Component\Exceptions\InvalidCallException;
use CraftCms\Cms\Component\Exceptions\UnknownPropertyException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Support\Utils;
use CraftCms\Cms\Validation\Attributes\Ruleset as RulesetAttribute;
use CraftCms\Cms\Validation\Contracts\Validatable;
use CraftCms\Cms\Validation\Ruleset;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;
use ReflectionClass;

/**
 * @mixin Validatable
 */
trait Validates
{
    use HasScenarios;
    use InteractsWithValidator;

    private Ruleset|false|null $ruleset = null;

    public function getRules(): array
    {
        return [];
    }

    public function getMessages(): array
    {
        return [];
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        Typecast::properties(static::class, $values);

        foreach ($values as $name => $value) {
            try {
                $this->$name = $value;
            } catch (UnknownPropertyException|InvalidCallException|\yii\base\UnknownPropertyException) {
                // Property or setter doesn't exist
            }
        }
    }

    public function getAttributes(): array
    {
        return Utils::getPublicProperties($this);
    }

    public function attributes(): array
    {
        return Utils::getPublicAttributes($this);
    }

    public function attributeLabels(): array
    {
        return [];
    }

    public function getRuleset(): Ruleset|false
    {
        if (isset($this->ruleset)) {
            return $this->ruleset;
        }

        $attributes = new ReflectionClass($this)->getAttributes(RulesetAttribute::class);

        $class = null;
        if (isset($attributes[0])) {
            $class = $attributes[0]->getArguments()[0];
        } elseif (method_exists($this, 'rulesClass')) {
            $class = $this->rulesClass();
        }

        if (is_null($class)) {
            return $this->ruleset = false;
        }

        if (! is_subclass_of($class, Ruleset::class)) {
            throw new BadMethodCallException('The rules class must be an instance of '.Ruleset::class);
        }

        return $this->ruleset = app()->make($class, ['component' => $this]);
    }

    protected function getValidator(?array $attributeNames = null): Validator
    {
        $ruleset = $this->getRuleset();
        $rules = $ruleset ? $ruleset->rules() : $this->getRules();
        $attributes = $ruleset ? $ruleset->attributes() : $this->attributeLabels();
        $messages = $ruleset ? $ruleset->messages() : $this->getMessages();

        return ValidatorFacade::make([], [])
            ->setData($this->getAttributes())
            ->setCustomMessages($messages)
            ->setAttributeNames($attributes)
            ->setRules(is_null($attributeNames)
                ? $rules
                : Arr::only($rules, $attributeNames)
            );
    }
}
