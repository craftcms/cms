<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use CraftCms\Cms\Activity\Contracts\ActivityEventTypeInterface;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizerManager;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Throwable;

use function CraftCms\Cms\t;

#[Scoped]
class Activities
{
    public function __construct(
        private readonly HtmlSanitizerManager $htmlSanitizers,
        private readonly ActivityEventRecorder $events,
        private readonly ActivityComments $comments,
    ) {}

    public function record(ActivityEventTypeInterface $event): ActivityEvent
    {
        return $this->events->record($event);
    }

    public function createComment(
        ElementInterface $subject,
        User $author,
        Site $site,
        string $markdown,
    ): ActivityEvent {
        return $this->comments->create($subject, $author, $site, $markdown);
    }

    public function editComment(
        ActivityEvent $comment,
        User $author,
        string $markdown,
        ?ElementInterface $subject = null,
    ): ActivityEvent {
        return $this->comments->edit($comment, $author, $markdown, $subject);
    }

    public function deleteComment(ActivityEvent $comment, User $actor): ActivityEvent
    {
        return $this->comments->delete($comment, $actor);
    }

    public function canMention(User $user, ElementInterface $subject): bool
    {
        return $this->comments->canMention($user, $subject);
    }

    public function renderComment(ActivityEvent $version): HtmlString
    {
        return $this->comments->render($version);
    }

    /** @return Builder<ActivityEvent> */
    public function query(): Builder
    {
        return ActivityEvent::query()->newestFirst();
    }

    public function format(ActivityEvent $event): string|Htmlable
    {
        $type = $event->eventType;

        if (! is_a($type, ActivityEventTypeInterface::class, true)) {
            return $this->capturedLabel($event);
        }

        try {
            $formatted = $type::format($event);

            if ($formatted === null) {
                return t(
                    $type::label(),
                    category: $type::source()->translationCategory,
                ) ?: $this->capturedLabel($event);
            }

            if (is_string($formatted)) {
                return $this->htmlSanitizers->sanitize($formatted);
            }

            return new HtmlString($this->htmlSanitizers->sanitize($formatted->toHtml()));
        } catch (Throwable $exception) {
            report($exception);

            return $this->capturedLabel($event);
        }
    }

    public function icon(ActivityEvent $event): ?string
    {
        $type = $event->eventType;

        if (! is_a($type, ActivityEventTypeInterface::class, true)) {
            return null;
        }

        return $type::icon();
    }

    private function capturedLabel(ActivityEvent $event): string
    {
        return $event->snapshots['event']['label'] ?? $event->eventType;
    }
}
