<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Filesystem\Uploads;
use CraftCms\Cms\GarbageCollection\GarbageCollection;
use RuntimeException;

class RemoveExpiredUploads extends GarbageCollectionAction
{
    public function __construct(
        GarbageCollection $garbageCollection,
        GeneralConfig $generalConfig,
        private readonly Uploads $uploads,
    ) {
        parent::__construct($garbageCollection, $generalConfig);
    }

    public function __invoke(): void
    {
        $this->components->task('removing expired upload sessions', function () {
            $result = $this->uploads->cleanupExpired();

            if ($result['failed'] > 0) {
                throw new RuntimeException('Some upload sessions could not be cleaned up. See the application log for details.');
            }
        });
    }
}
