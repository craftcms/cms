<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Workflows;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementEditorActions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Requests\ActivityCommentRequest;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\ViewModels\EntryEditViewModel;
use CraftCms\Cms\Release\Data\ReleaseSummaryData;
use CraftCms\Cms\Workflow\Models\WorkflowRun;
use CraftCms\Cms\Workflow\Workflows;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
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
            'note' => ['nullable', 'string', 'max:'.ActivityCommentRequest::MaxLength],
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
            'note' => ['required', 'string', 'max:'.ActivityCommentRequest::MaxLength],
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
            'note' => ['nullable', 'string', 'max:'.ActivityCommentRequest::MaxLength],
        ]);

        $this->workflows->overrideApproval(
            draft: $draft,
            runId: $workflowRun->id,
            reason: $data['note'] ?? null,
        );

        return $this->response($draft, t('Workflow approval overridden.'));
    }

    public function restart(WorkflowRun $workflowRun): JsonResponse
    {
        $draft = $this->draft();

        $this->workflows->restartWorkflow($draft, $workflowRun->id);

        return $this->response($draft, t('Workflow restarted.'));
    }

    public function prepareForEditing(WorkflowRun $workflowRun): JsonResponse
    {
        $draft = $this->draft();

        $this->workflows->prepareForEditing($draft, $workflowRun->id);

        return $this->response($draft);
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
            'release' => $this->releaseState($draft),
            ...($message !== null ? ['message' => $message] : []),
        ]);
    }

    /**
     * @return array{
     *     canAddToRelease: bool,
     *     canCreateRelease: bool,
     *     releaseOptions: list<ReleaseSummaryData>,
     * }|null
     */
    private function releaseState(ElementInterface $draft): ?array
    {
        if (! $draft instanceof Entry) {
            return null;
        }

        $viewModel = new EntryEditViewModel(
            entry: $draft,
            request: $this->request,
            canSave: $this->request->craftUser()->can('save', $draft),
        );

        return [
            'canAddToRelease' => $viewModel->canAddToRelease(),
            'canCreateRelease' => $viewModel->canCreateRelease(),
            'releaseOptions' => $viewModel->releaseOptions(),
        ];
    }
}
