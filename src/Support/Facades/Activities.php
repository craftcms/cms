<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Closure;
use CraftCms\Cms\Activity\Data\ActivityActor;
use CraftCms\Cms\Activity\Data\ActivitySource;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void extend(string $eventType, ActivitySource $source, string $label, string $icon = 'wave-pulse', array $rules = [], (Closure(ActivityEvent, string): (string|Htmlable))|null $formatter = null)
 * @method static ActivityEvent record(string $eventType, ElementInterface|ActivitySubject|null $subject = null, User|ActivityActor|null $actor = null, ?Site $site = null, array $data = [], array $changes = [])
 * @method static Builder<ActivityEvent> query()
 * @method static string|Htmlable format(ActivityEvent $event, ?string $locale = null)
 * @method static string icon(ActivityEvent $event)
 *
 * @see \CraftCms\Cms\Activity\Activities
 */
class Activities extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Activity\Activities::class;
    }
}
