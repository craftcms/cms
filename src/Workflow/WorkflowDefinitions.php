<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Workflow\Contracts\WorkflowStageInterface;
use CraftCms\Cms\Workflow\Data\WorkflowStageData;
use CraftCms\Cms\Workflow\Models\Workflow;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

/**
 * Validates workflow definitions, wherever they come from.
 *
 * @since 6.0.0
 */
readonly class WorkflowDefinitions
{
    public function __construct(private WorkflowStageTypes $stageTypes) {}

    /**
     * The rules a workflow’s name and stages must pass.
     *
     * @return array<string, list<string|object>>
     */
    public function rules(?Workflow $workflow = null): array
    {
        $types = $this->stageTypes->types();

        // Keep existing plugin-provided stage types valid if their plugin is unavailable.
        if ($workflow !== null) {
            $types->push(...$workflow->stages->pluck('type'));
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'stages' => ['required', 'array', 'min:1'],
            'stages.*.uid' => ['required', 'uuid', 'distinct'],
            'stages.*.name' => ['required', 'string', 'max:255'],
            'stages.*.type' => ['required', 'string', Rule::in($types->unique()->all())],
            'stages.*.settings' => ['present', 'array'],
        ];
    }

    /**
     * Validates each stage’s settings with its stage type, once the rules have passed.
     *
     * @param  array<int|string, array{type: string, settings: array<string, mixed>}>  $stages
     */
    public function validateStageSettings(Validator $validator, array $stages): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        foreach ($stages as $index => $stage) {
            if (! ComponentHelper::validateComponentClass($stage['type'], WorkflowStageInterface::class)) {
                continue;
            }

            /** @var WorkflowStageInterface $component */
            $component = ComponentHelper::createComponent([
                'type' => $stage['type'],
                'settings' => $stage['settings'],
            ], WorkflowStageInterface::class);

            if ($component->validate()) {
                continue;
            }

            foreach ($component->errors()->getMessages() as $attribute => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add("stages.$index.settings.$attribute", $message);
                }
            }
        }
    }

    /**
     * Validates a workflow definition and returns the values to fill the workflow with.
     *
     * @param  array<string, mixed>  $input
     * @return array{name: string, stages: list<WorkflowStageData>}
     *
     * @throws ValidationException
     */
    public function validate(array $input, ?Workflow $workflow = null): array
    {
        $validator = ValidatorFacade::make($input, $this->rules($workflow));
        $validator->after(fn (Validator $validator) => $this->validateStageSettings($validator, $input['stages'] ?? []));

        return $this->workflowData($validator->validate());
    }

    /**
     * @param  array<string, mixed>  $data  Validated input.
     * @return array{name: string, stages: list<WorkflowStageData>}
     */
    public function workflowData(array $data): array
    {
        return [
            'name' => $data['name'],
            'stages' => array_values(array_map(fn (array $stage): WorkflowStageData => new WorkflowStageData(
                uid: $stage['uid'],
                name: $stage['name'],
                type: $stage['type'],
                settings: $stage['settings'],
            ), $data['stages'])),
        ];
    }
}
