<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Activity\Contracts\ActivityEventTypeInterface;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HtmlString;

/**
 * @method static ActivityEvent record(ActivityEventTypeInterface $event)
 * @method static ActivityEvent createComment(ElementInterface $subject, User $author, Site $site, string $markdown)
 * @method static ActivityEvent editComment(ActivityEvent $comment, User $author, string $markdown, ?ElementInterface $subject = null)
 * @method static ActivityEvent deleteComment(ActivityEvent $comment, User $actor)
 * @method static bool canMention(User $user, ElementInterface $subject)
 * @method static HtmlString renderComment(ActivityEvent $version)
 * @method static Builder<ActivityEvent> query()
 * @method static string|Htmlable format(ActivityEvent $event)
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
