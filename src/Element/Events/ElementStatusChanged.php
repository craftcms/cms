<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\SchedulableElementInterface;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * Dispatched after an element’s status changes in a site and the change is committed, such as when an entry goes
 * live, expires, or is enabled, disabled or archived.
 *
 * Craft dispatches it once for each site whose status changed. Saves dispatch it, including applying a draft and
 * propagating an element to a new site. When a post or expiry date passes, the next run of the
 * `craft:update-element-statuses` command dispatches it. Craft schedules that command every minute.
 *
 * Only {@see SchedulableElementInterface} element types dispatch it, and only for canonical elements, not drafts or
 * revisions. Deleting and restoring an element don’t dispatch it. If a date passed while the element was deleted, the
 * command’s first run after the restore dispatches it.
 *
 * ```php
 * use CraftCms\Cms\Element\Events\ElementStatusChanged;
 * use CraftCms\Cms\Entry\Elements\Entry;
 *
 * Event::listen(function (ElementStatusChanged $event) {
 *     if ($event->element instanceof Entry && $event->newStatus === Entry::STATUS_LIVE) {
 *         // The entry just went live in $event->element->siteId
 *     }
 * });
 * ```
 *
 * @since 6.0.0
 */
class ElementStatusChanged implements ShouldDispatchAfterCommit
{
    public function __construct(
        /** The element, in the site whose status changed */
        public ElementInterface $element,
        /** The element’s previous status in the site, or `null` if it’s new to the site */
        public ?string $oldStatus,
        /** The element’s new status in the site, as returned by {@see ElementInterface::getStatus()} */
        public string $newStatus,
    ) {}
}
