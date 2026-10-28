<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use CraftCms\Cms\Workflow\Data\WorkflowStageData;
use CraftCms\Cms\Workflow\Models\Workflow;
use CraftCms\Cms\Workflow\WorkflowDefinitions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * @since 6.0.0
 */
class WorkflowRequest extends FormRequest
{
    /** @return array<string, list<string|object>> */
    public function rules(WorkflowDefinitions $definitions): array
    {
        $workflow = $this->route('workflow');

        return $definitions->rules($workflow instanceof Workflow ? $workflow : null);
    }

    /** @return list<callable> */
    public function after(WorkflowDefinitions $definitions): array
    {
        return [
            fn (Validator $validator) => $definitions->validateStageSettings($validator, $this->input('stages')),
        ];
    }

    /** @return array{name: string, stages: list<WorkflowStageData>} */
    public function workflowData(): array
    {
        return app(WorkflowDefinitions::class)->workflowData($this->validated());
    }
}
