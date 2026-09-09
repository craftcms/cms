<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Condition\ConditionBuilder;
use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Support\Facades\HtmlStack;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

use function CraftCms\Cms\t;

readonly class ConditionsController
{
    public function __construct(private ConditionBuilder $builder) {}

    public function show(Request $request): JsonResponse
    {
        $condition = $this->condition($request);

        return new JsonResponse([
            'builder' => $this->builder->resolve($condition, $request->boolean('editable', true)),
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
        ]);
    }

    public function rule(Request $request): JsonResponse
    {
        $condition = $this->condition($request);

        $request->validate([
            'rule' => ['required', 'array'],
            'rule.class' => ['nullable', 'string'],
            'rule.type' => ['required_without:rule.class', 'string'],
            'rule.uid' => ['nullable', 'uuid'],
        ]);

        try {
            $rule = $condition->createConditionRule($request->array('rule'));
            $condition->addConditionRule($rule);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['rule' => t('The selected condition rule is invalid.')]);
        }

        return new JsonResponse([
            'rule' => $this->builder->resolveRule($rule, $request->boolean('editable', true)),
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
        ]);
    }

    public function validate(Request $request, Conditions $conditions): JsonResponse
    {
        $condition = $this->condition($request);
        $errors = $conditions->validate($condition);

        if ($errors !== []) {
            throw ValidationException::withMessages(
                collect($errors)->mapWithKeys(fn (array $messages, string $path) => ["_conditionRules.$path" => $messages])->all(),
            );
        }

        return new JsonResponse(['valid' => true]);
    }

    private function condition(Request $request): ConditionInterface
    {
        $request->validate([
            'config' => ['required', 'array'],
            'config.class' => ['required', 'string'],
            'config.forProjectConfig' => ['sometimes', 'boolean'],
            'config.forQuery' => ['sometimes', 'boolean'],
            'value' => ['present', 'array'],
            'value.class' => ['sometimes', 'string', 'same:config.class'],
            'value.conditionRules' => ['sometimes', 'array'],
            'editable' => ['sometimes', 'boolean'],
        ]);

        try {
            return $this->builder->createCondition($request->array('value'), $request->array('config'));
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['config' => t('The posted condition config is invalid.')]);
        }
    }
}
