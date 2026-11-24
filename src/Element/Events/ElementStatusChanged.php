<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\SchedulableElementInterface;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * Dispatched when an element’s status in a site changes, once the change is committed. For example, when an entry goes
 * live or expires, or is enabled, disabled or archived.
 *
 * It’s dispatched once for each site whose status changed, whether a save changed it or a date passed. Craft notices
 * passed dates from the `craft:update-element-statuses` command, which it schedules every minute, so a status that
 * changes because a date passed is dispatched by that command’s next run.
 *
 * Only {@see SchedulableElementInterface} element types dispatch it, and only for canonical elements, not drafts or
 * revisions. Applying a draft and propagating an element to new sites dispatch it too. Deleting and restoring an
 * element don’t; a date that passed while an element was deleted is dispatched by the command’s first run after it’s
 * restored.
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
