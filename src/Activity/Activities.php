<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use CraftCms\Cms\Activity\Contracts\ActivityEventTypeInterface;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizerManager;
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
    ) {}

    public function record(ActivityEventTypeInterface $event): ActivityEvent
    {
        return $this->events->record($event);
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

    public function icon(ActivityEvent $event): string
    {
        $type = $event->eventType;

        if (! is_a($type, ActivityEventTypeInterface::class, true)) {
            return 'wave-pulse';
        }

        return $type::icon() ?: 'wave-pulse';
    }

    private function capturedLabel(ActivityEvent $event): string
    {
        return $event->snapshots['event']['label'] ?? $event->eventType;
    }
}
