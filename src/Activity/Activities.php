<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use Closure;
use CraftCms\Cms\Activity\Data\ActivityActor;
use CraftCms\Cms\Activity\Data\ActivitySource;
use CraftCms\Cms\Activity\Data\ActivitySubject;
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
use UnexpectedValueException;

#[Scoped]
class Activities
{
    public function __construct(
        private readonly ActivityEventTypes $eventTypes,
        private readonly HtmlSanitizerManager $htmlSanitizers,
        private readonly ActivityEventRecorder $events,
    ) {}

    /**
     * @param  array<string, mixed>  $rules
     * @param  (Closure(ActivityEvent, string): (string|Htmlable))|null  $formatter
     */
    public function extend(
        string $eventType,
        ActivitySource $source,
        string $label,
        string $icon = 'wave-pulse',
        array $rules = [],
        ?Closure $formatter = null,
    ): void {
        $this->eventTypes->register(
            eventType: $eventType,
            source: $source,
            label: $label,
            icon: $icon,
            rules: $rules,
            formatter: $formatter,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $changes
     */
    public function record(
        string $eventType,
        ElementInterface|ActivitySubject|null $subject = null,
        User|ActivityActor|null $actor = null,
        ?Site $site = null,
        array $data = [],
        array $changes = [],
    ): ActivityEvent {
        return $this->events->record($eventType, $subject, $actor, $site, $data, $changes);
    }

    /** @return Builder<ActivityEvent> */
    public function query(): Builder
    {
        return ActivityEvent::query()->newestFirst();
    }

    public function format(ActivityEvent $event, ?string $locale = null): string|Htmlable
    {
        $registration = $this->eventTypes->find($event->eventType);

        if ($registration === null) {
            return $this->capturedLabel($event);
        }

        $locale ??= app()->getLocale();

        try {
            if ($registration['formatter'] === null) {
                return $this->eventTypes->label($event->eventType, $locale);
            }

            $formatted = ($registration['formatter'])($event, $locale);

            if (is_string($formatted)) {
                return $formatted;
            }

            if (! $formatted instanceof Htmlable) {
                throw new UnexpectedValueException('Activity event formatters must return plain text or safe HTML.');
            }

            return new HtmlString($this->htmlSanitizers->sanitize($formatted->toHtml()));
        } catch (Throwable $exception) {
            report($exception);

            return $this->capturedLabel($event);
        }
    }

    public function icon(ActivityEvent $event): string
    {
        return $this->eventTypes->icon($event->eventType);
    }

    private function capturedLabel(ActivityEvent $event): string
    {
        return $event->snapshots['event']['label'] ?? $event->eventType;
    }
}
