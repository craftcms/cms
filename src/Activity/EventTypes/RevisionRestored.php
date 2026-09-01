<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Site\Data\Site;

use function CraftCms\Cms\t;

class RevisionRestored extends ActivityEventType
{
    protected const string LABEL = 'Revision restored';

    protected const string ICON = 'rotate-left';

    public function __construct(
        ElementInterface $subject,
        ?Site $site,
        private readonly int $revisionNum,
    ) {
        parent::__construct(subject: $subject, site: $site);
    }

    public function data(): array
    {
        return ['revisionNum' => $this->revisionNum];
    }

    public static function format(ActivityEvent $event): string
    {
        return t(
            'Restored revision {revision}.',
            ['revision' => $event->data['revisionNum']],
        );
    }
}
