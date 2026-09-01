<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

use function CraftCms\Cms\t;

readonly class ConditionsController
{
    private ConditionInterface $condition;

    public function __construct(
        private Request $request,
        private Conditions $conditions,
    ) {
        $this->request->validate([
            'config' => ['required', 'json'],
        ]);

        $baseConfig = Json::decode($this->request->input('config'));

        Validator::make($baseConfig, [
            'name' => ['required', 'string'],
            'class' => ['required', 'string'],
            'id' => ['nullable', 'string'],
            'mainTag' => ['nullable', 'string'],
            'sortable' => ['nullable', 'boolean'],
            'forProjectConfig' => ['nullable', 'boolean'],
            'addRuleLabel' => ['nullable', 'string'],
        ])->validate();

        if (! is_subclass_of($baseConfig['class'], ConditionInterface::class)) {
            throw ValidationException::withMessages([
                'config' => [t('The posted condition config is invalid.')],
            ]);
        }

        $name = Arr::dotifyKey($baseConfig['name']);
        $config = $this->request->input($name);

        Validator::make($this->request->all(), [
            $name => ['required', 'array'],
            "$name.class" => ['required', 'string'],
            "$name.config" => ['nullable', 'json'],
            "$name.conditionRules" => ['nullable', 'array'],
            "$name.new-rule-type" => ['nullable'],
        ])->validate();

        if (($config['class'] ?? null) !== $baseConfig['class']) {
            throw ValidationException::withMessages([
                "$name.class" => [t('The selected condition class is invalid.')],
            ]);
        }

        $conditionClass = $config['class'];

        if (! is_subclass_of($conditionClass, ConditionInterface::class)) {
            throw ValidationException::withMessages([
                "$name.class" => [t('The selected condition class is invalid.')],
            ]);
        }

        $config['class'] = $conditionClass;

        $newRuleType = Arr::pull($config, 'new-rule-type');

        $this->condition = $this->createCondition($conditionClass, $config);

        Typecast::configure($this->condition, Arr::except($baseConfig, 'class'));

        if ($newRuleType) {
            $newRuleType = Json::decodeIfJson($newRuleType);
            $rule = $this->condition->createConditionRule($newRuleType);
            $rule->setAutofocus();
            $this->condition->addConditionRule($rule);
        }
    }

    /**
     * @param  class-string<ConditionInterface>  $conditionClass
     * @param  array<string, mixed>  $config
     */
    private function createCondition(string $conditionClass, array $config): ConditionInterface
    {
        return $this->conditions->createCondition(['class' => $conditionClass] + $config);
    }

    public function show(): string
    {
        return $this->condition->getBuilderInnerHtml();
    }

    public function store(): string
    {
        /** @var ConditionRuleInterface|null $rule */
        $rule = collect($this->condition->getSelectableConditionRules())
            ->sortBy(fn (ConditionRuleInterface $rule) => $rule->getLabel())
            ->first();

        if ($rule) {
            $rule->setAutofocus();
            $this->condition->addConditionRule($rule);
        }

        return $this->condition->getBuilderInnerHtml();
    }

    public function destroy(): string
    {
        $this->request->validate([
            'uid' => ['required', 'uuid'],
        ]);

        $ruleUid = $this->request->input('uid');
        $conditionRules = collect($this->condition->getConditionRules())
            ->filter(fn (ConditionRuleInterface $rule) => $rule->uid !== $ruleUid)
            ->all();

        $this->condition->setConditionRules($conditionRules);

        return $this->condition->getBuilderInnerHtml(true);
    }
}
