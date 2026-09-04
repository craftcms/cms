<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Site\Data\Site;

use function CraftCms\Cms\t;

class AssetFileReplaced extends ActivityEventType
{
    protected const string LABEL = 'File replaced';

    protected const string ICON = 'file-arrow-up';

    public function __construct(
        Asset $subject,
        ?Site $site,
        private readonly string $oldFilename,
        private readonly string $newFilename,
        private readonly ?string $oldMimeType,
        private readonly ?string $newMimeType,
        private readonly ?int $oldSize,
        private readonly ?int $newSize,
    ) {
        parent::__construct(subject: $subject, site: $site);
    }

    public function data(): array
    {
        return [
            'oldFilename' => $this->oldFilename,
            'newFilename' => $this->newFilename,
            'oldMimeType' => $this->oldMimeType,
            'newMimeType' => $this->newMimeType,
            'oldSize' => $this->oldSize,
            'newSize' => $this->newSize,
        ];
    }

    public static function format(ActivityEvent $event): string
    {
        return t(
            'Replaced {oldFilename} with {newFilename}.',
            [
                'oldFilename' => $event->data['oldFilename'],
                'newFilename' => $event->data['newFilename'],
            ],
        );
    }
}
