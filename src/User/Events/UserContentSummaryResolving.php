<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use Illuminate\Support\Collection;

/**
 * @event UserContentSummaryResolving The event that is triggered when defining a summary of content owned by a user(s), before they are deleted
 *
 * ---
 * ```php
 * use CraftCms\Cms\User\Events\UserContentSummaryResolving;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(UserContentSummaryResolving::class, function(UserContentSummaryResolving $e) {
 *     $e->contentSummary->push('A pair of sneakers');
 * });
 * ```
 *
 * @since 3.0.13
 */
class UserContentSummaryResolving
{
    public function __construct(
        /**
         * @var int|int[] The user ID(s) associated with the event
         */
        public int|array $userId,

        /**
         * @var Collection<string> Summary of content that is owned by the user(s)
         */
        public Collection $contentSummary,
    ) {}
}
