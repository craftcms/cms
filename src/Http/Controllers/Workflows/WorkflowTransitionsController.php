<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Workflows;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementEditorActions;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Workflow\Models\WorkflowRun;
use CraftCms\Cms\Workflow\Workflows;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

class WorkflowTransitionsController
{
    public function __construct(
        private readonly ElementRequest $request,
        private readonly Workflows $workflows,
        private readonly ElementEditorActions $editorActions,
    ) {}

    public function submit(): JsonResponse
    {
        $draft = $this->draft();
        $data = $this->request->validate([
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->workflows->submitForReview(
            draft: $draft,
            note: $data['note'] ?? null,
        );

        return $this->response($draft, t('Review submitted.'));
    }

    public function comment(WorkflowRun $workflowRun, string $stage): JsonResponse
    {
        $draft = $this->draft();
        $data = $this->request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $this->workflows->addComment(
            draft: $draft,
            runId: $workflowRun->id,
            stage: $stage,
            note: $data['note'],
        );

        return $this->response($draft, t('Comment added.'));
    }

    public function override(WorkflowRun $workflowRun): JsonResponse
    {
        $draft = $this->draft();
        $data = $this->request->validate([
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->workflows->overrideApproval(
            draft: $draft,
            runId: $workflowRun->id,
            reason: $data['note'] ?? null,
        );

        return $this->response($draft, t('Workflow approval overridden.'));
    }

    private function draft(): ElementInterface
    {
        $element = $this->request->element();
        abort_unless($element instanceof ElementInterface && $element->getIsDraft(), Response::HTTP_BAD_REQUEST, 'A draft element is required.');

        return $element;
    }

    private function response(ElementInterface $draft, ?string $message = null): JsonResponse
    {
        $review = $this->workflows->reviewData(
            draft: $draft,
            viewer: $this->request->craftUser(),
        );

        return new JsonResponse([
            'workflowReview' => $review,
            'editorActions' => $this->editorActions->for(
                element: $draft,
                canSave: $this->request->craftUser()->can('save', $draft),
                review: $review,
            ),
            ...($message !== null ? ['message' => $message] : []),
        ]);
    }
}
