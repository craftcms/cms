<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Activity;

use CraftCms\Cms\Activity\ActivityEventType;
use CraftCms\Cms\Activity\Contracts\ShouldBeRetained;
use CraftCms\Cms\Activity\Data\ActivityActor;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Markdown\Markdown as MarkdownService;
use CraftCms\Cms\Support\Facades\HtmlSanitizers;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\Workflow\Enums\WorkflowActivityType;
use Illuminate\Contracts\Support\Htmlable;

use function CraftCms\Cms\t;

class WorkflowActivityEvent extends ActivityEventType implements ShouldBeRetained
{
    protected const string LABEL = 'Workflow review';

    protected const string ICON = 'clipboard-list-check';

    public function __construct(
        ElementInterface $subject,
        private readonly WorkflowActivityType $type,
        private readonly ?string $stage = null,
        private readonly ?int $stageNumber = null,
        private readonly ?string $note = null,
        private readonly ?int $runId = null,
        /** @var array{id: int, uid: string, name: string, stages: list<array{uid: string, name: string, type: string, settings: array<string, mixed>}>}|null */
        private readonly ?array $workflow = null,
        /** @var array{elementId: int, draftId: int, elementType: string, siteId: int}|null */
        private readonly ?array $draft = null,
        CraftUser|ActivityActor|null $actor = null,
    ) {
        parent::__construct(subject: $subject, actor: $actor);
    }

    public function data(): array
    {
        return [
            'type' => $this->type->value,
            'stage' => $this->stage,
            'stageNumber' => $this->stageNumber,
            'note' => $this->note,
            ...($this->runId === null ? [] : ['runId' => $this->runId]),
            ...($this->workflow === null ? [] : ['workflow' => $this->workflow]),
            ...($this->draft === null ? [] : ['draft' => $this->draft]),
        ];
    }

    public static function format(ActivityEvent $event): string|Htmlable
    {
        $stage = $event->data['stage'] ?? null;
        $translatedStage = $stage === null ? null : t($stage);
        $type = WorkflowActivityType::from($event->data['type']);

        return match ($type) {
            WorkflowActivityType::Submit => t('Submitted for review in “{workflow}”.', ['workflow' => $event->data['workflow']['name']]),
            WorkflowActivityType::Comment => t('Commented on the “{stage}” review stage.', ['stage' => $translatedStage]),
            WorkflowActivityType::Override => t('Overrode the workflow approval.'),
            WorkflowActivityType::Approve => t('Approved the “{stage}” review stage.', ['stage' => $translatedStage]),
            WorkflowActivityType::Reject => t('Requested changes during the “{stage}” review stage.', ['stage' => $translatedStage]),
            WorkflowActivityType::RequestReview => t('Requested another review of the “{stage}” stage.', ['stage' => $translatedStage]),
            WorkflowActivityType::Restart => t('Restarted the workflow review.'),
            WorkflowActivityType::StageApproved => t('The “{stage}” stage was approved.', ['stage' => $translatedStage]),
            WorkflowActivityType::StageFailed => t('The “{stage}” stage failed.', ['stage' => $translatedStage]),
            WorkflowActivityType::Invalidate => t('Invalidated the workflow review.'),
            WorkflowActivityType::Publish => t('Published the approved draft.'),
        };
    }

    public static function component(): string
    {
        return 'craft:workflow-activity-event';
    }

    public static function props(ActivityEvent $event): array
    {
        $note = $event->data['note'] ?? null;

        if (! $note) {
            return ['noteHtml' => null];
        }

        return [
            'noteHtml' => HtmlSanitizers::sanitize(
                Markdown::parse($note, MarkdownService::FLAVOR_GFM_COMMENT),
            ),
        ];
    }
}
