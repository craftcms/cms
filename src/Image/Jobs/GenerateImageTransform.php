<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Jobs;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\ImageTransformer;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Support\Facades\I18N;
use Illuminate\Support\Facades\Log;
use Override;
use Throwable;

class GenerateImageTransform extends Job
{
    public function __construct(
        public int $transformId,
        protected ?string $description = null,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $transformer = new ImageTransformer;
        $index = $transformer->getTransformIndexModelById($this->transformId);

        if ($index && ! $index->fileExists) {
            // Don't let an exception stop us from processing the rest
            try {
                /** @var Asset|null $asset */
                $asset = Asset::find()->id($index->assetId)->one();

                if ($asset) {
                    $transformer->getTransformUrl($asset, $index->getTransform(), true);
                }
            } catch (Throwable $e) {
                Log::warning('Image transform generation failed: '.$e->getMessage(), [__METHOD__]);
            }
        }
    }

    #[Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Generating image transform');
    }
}
