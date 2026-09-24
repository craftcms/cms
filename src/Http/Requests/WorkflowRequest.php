<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Workflow\Contracts\WorkflowStageInterface;
use CraftCms\Cms\Workflow\Data\WorkflowStageData;
use CraftCms\Cms\Workflow\Models\Workflow;
use CraftCms\Cms\Workflow\WorkflowStageTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class WorkflowRequest extends FormRequest
{
    /** @return array<string, list<string|object>> */
    public function rules(WorkflowStageTypes $workflowStageTypes): array
    {
        $types = $workflowStageTypes->types();
        $workflow = $this->route('workflow');

        // Keep existing plugin-provided stage types valid if their plugin is unavailable.
        if ($workflow instanceof Workflow) {
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

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                foreach ($this->input('stages') as $index => $stage) {
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
            },
        ];
    }

    /** @return array{name: string, stages: list<WorkflowStageData>} */
    public function workflowData(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'stages' => array_map(fn (array $stage): WorkflowStageData => new WorkflowStageData(
                uid: $stage['uid'],
                name: $stage['name'],
                type: $stage['type'],
                settings: $stage['settings'],
            ), $data['stages']),
        ];
    }
}
