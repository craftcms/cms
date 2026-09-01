<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;
use CraftCms\Cms\Activity\Data\ActivityChange;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Str;

use function CraftCms\Cms\t;

class ElementStatusChanged extends ActivityEventType
{
    protected const string LABEL = 'Status changed';

    protected const string ICON = 'circle-half-stroke';

    /**
     * @param  list<ActivityChange>  $changes
     */
    public function __construct(
        ElementInterface $subject,
        ?Site $site,
        private readonly string $oldStatus,
        private readonly string $newStatus,
        array $changes = [],
    ) {
        parent::__construct(subject: $subject, site: $site, changes: $changes);
    }

    public function data(): array
    {
        return [
            'oldStatus' => $this->oldStatus,
            'newStatus' => $this->newStatus,
        ];
    }

    public static function format(ActivityEvent $event): string
    {
        return t(
            'Status changed from {oldStatus} to {newStatus}.',
            [
                'oldStatus' => t(Str::headline($event->data['oldStatus'])),
                'newStatus' => t(Str::headline($event->data['newStatus'])),
            ],
        );
    }
}
