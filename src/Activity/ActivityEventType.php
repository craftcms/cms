<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use CraftCms\Cms\Activity\Contracts\ActivityEventTypeInterface;
use CraftCms\Cms\Activity\Data\ActivityActor;
use CraftCms\Cms\Activity\Data\ActivitySource;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Support\Htmlable;

abstract class ActivityEventType implements ActivityEventTypeInterface
{
    protected const string LABEL = '';

    protected const string ICON = 'wave-pulse';

    /**
     * @param  list<array<string, mixed>>  $changes
     */
    public function __construct(
        private readonly ElementInterface|ActivitySubject|null $subject = null,
        private readonly User|ActivityActor|null $actor = null,
        private readonly ?Site $site = null,
        private readonly array $changes = [],
    ) {}

    public function subject(): ?ActivitySubject
    {
        return $this->subject instanceof ElementInterface
            ? ActivitySubject::fromElement($this->subject)
            : $this->subject;
    }

    public function actor(): ?ActivityActor
    {
        return $this->actor instanceof User
            ? ActivityActor::user($this->actor)
            : $this->actor;
    }

    public function site(): ?Site
    {
        return $this->site;
    }

    public function data(): array
    {
        return [];
    }

    public function changes(): array
    {
        return $this->changes;
    }

    public static function source(): ActivitySource
    {
        return new ActivitySource(
            id: 'craft',
            label: 'Craft',
            translationCategory: 'app',
        );
    }

    public static function label(): string
    {
        return static::LABEL;
    }

    public static function icon(): string
    {
        return static::ICON;
    }

    public static function rules(): array
    {
        return [];
    }

    public static function format(ActivityEvent $event): string|Htmlable|null
    {
        return null;
    }
}
