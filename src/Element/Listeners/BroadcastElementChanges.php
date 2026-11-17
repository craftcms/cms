<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Listeners;

use CraftCms\Cms\Element\Events\ElementDeleted;
use CraftCms\Cms\Element\Events\ElementSaved;
use CraftCms\Cms\Http\Middleware\SendBroadcasts;

/**
 * Tells every open control panel tab when an element is saved or a draft is deleted during a control panel request,
 * so their chips, cards and editors can catch up.
 *
 * The messages go out with the response: as an Inertia page's `broadcasts` prop, a legacy page's flashed JS, or an
 * `X-Craft-Broadcast` header ({@see SendBroadcasts}).
 *
 * @internal
 *
 * @since 6.0.0
 */
class BroadcastElementChanges
{
    public function handleElementSaved(ElementSaved $event): void
    {
        $element = $event->element;

        if ($element->getIsDraft() || $element->getIsRevision() || $element->propagating || $element->resaving) {
            return;
        }

        session()->broadcastToJs([
            'event' => 'saveElement',
            'id' => $element->id,
        ]);
    }

    public function handleElementDeleted(ElementDeleted $event): void
    {
        $element = $event->element;

        if (! $element->getIsDraft()) {
            return;
        }

        session()->broadcastToJs([
            'event' => 'deleteDraft',
            'canonicalId' => $element->getCanonicalId(),
            'draftId' => $element->draftId,
        ]);
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(): array
    {
        return [
            ElementSaved::class => 'handleElementSaved',
            ElementDeleted::class => 'handleElementDeleted',
        ];
    }
}
