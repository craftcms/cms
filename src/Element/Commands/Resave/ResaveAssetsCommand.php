<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Commands\Resave;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Volumes;
use Override;

class ResaveAssetsCommand extends ResaveCommand
{
    #[Override]
    protected $signature = 'craft:resave:assets'
        .self::DEFAULT_OPTIONS
        .'{--volume= : Volume handle(s), comma-separated.}';

    #[Override]
    protected $description = 'Re-saves assets.';

    #[Override]
    protected $aliases = ['resave/assets'];

    public function handle(Volumes $volumes): int
    {
        if (! $this->validateResaveOptions()) {
            return self::FAILURE;
        }

        $criteria = [];

        if ($this->option('volume')) {
            $criteria['volume'] = str($this->option('volume'))
                ->explode(',')
                ->all();
        }

        $withFields = $this->resolvedWithFields();

        if (! empty($withFields)) {
            $handles = $volumes->getAllVolumes()
                ->filter(fn (Volume $volume) => $this->hasTheFields($volume->getFieldLayout()))
                ->map(fn (Volume $volume) => $volume->handle)
                ->all();

            if (isset($criteria['volume'])) {
                $criteria['volume'] = array_intersect($criteria['volume'], $handles);
            } else {
                $criteria['volume'] = $handles;
            }

            if (empty($criteria['volume'])) {
                $this->components->warn('No volumes satisfy `--with-fields`.');

                return self::FAILURE;
            }
        }

        return $this->resaveElements(Asset::class, $criteria);
    }
}
