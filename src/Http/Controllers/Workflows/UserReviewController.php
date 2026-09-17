<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Workflows;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Workflow\Data\WorkflowStageContext;
use CraftCms\Cms\Workflow\Enums\WorkflowTransition;
use CraftCms\Cms\Workflow\Exceptions\WorkflowException;
use CraftCms\Cms\Workflow\Models\WorkflowRun;
use CraftCms\Cms\Workflow\UserReview\UserReviewDecision;
use CraftCms\Cms\Workflow\UserReview\UserReviewStage;
use CraftCms\Cms\Workflow\Workflows;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

class UserReviewController
{
    public function __construct(
        private readonly ElementRequest $request,
        private readonly Workflows $workflows,
    ) {}

    public function approve(WorkflowRun $workflowRun, string $stage): JsonResponse
    {
        return $this->decide($workflowRun, $stage, UserReviewDecision::Approved);
    }

    public function requestChanges(WorkflowRun $workflowRun, string $stage): JsonResponse
    {
        return $this->decide($workflowRun, $stage, UserReviewDecision::Rejected);
    }

    private function decide(WorkflowRun $workflowRun, string $stage, UserReviewDecision $decision): JsonResponse
    {
        $draft = $this->draft();
        $data = $this->request->validate([
            'message' => [
                Rule::when($decision === UserReviewDecision::Rejected, 'required', 'nullable'),
                'string',
                'max:5000',
            ],
        ]);

        $message = $data['message'] ?? null;
        $transition = match ($decision) {
            UserReviewDecision::Approved => WorkflowTransition::Approve,
            UserReviewDecision::Rejected => WorkflowTransition::Reject,
        };

        $this->workflows->reportStageResult(
            runId: $workflowRun->id,
            stageUid: $stage,
            result: function (WorkflowStageContext $context) use ($decision, $message) {
                $component = $context->stage->component();
                if (! $component instanceof UserReviewStage) {
                    throw new WorkflowException('The current workflow stage does not accept user reviews.');
                }

                return $component->decide($decision, $message, $context, $this->request->craftUser());
            },
            actor: $this->request->craftUser(),
            activityTransition: $transition,
            activityNote: trim((string) $message) ?: null,
        );

        return new JsonResponse([
            'workflowReview' => $this->workflows->reviewData($draft, $this->request->craftUser()),
            'message' => t('Review updated.'),
        ]);
    }

    private function draft(): ElementInterface
    {
        $element = $this->request->element();
        abort_unless($element instanceof ElementInterface && $element->getIsDraft(), Response::HTTP_BAD_REQUEST, 'A draft element is required.');

        return $element;
    }
}
