<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Jobs;

use Craft;
use craft\imagetransforms\ImageTransformer;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Support\Facades\I18N;
use Throwable;

/**
 * Generates an image transform.
 *
 * @since 6.0.0
 */
final class GenerateImageTransform extends Job
{
    /**
     * Creates a new GenerateImageTransform job.
     *
     * @param  int  $transformId  The transform ID.
     */
    public function __construct(
        public int $transformId,
    ) {}

    public function handle(): void
    {
        $transformer = Craft::createObject(ImageTransformer::class);
        $index = $transformer->getTransformIndexModelById($this->transformId);

        if ($index && ! $index->fileExists) {
            // Don't let an exception stop us from processing the rest
            try {
                /** @var Asset|null $asset */
                $asset = Asset::find()->id($index->assetId)->one();

                if ($asset) {
                    $transformer->getTransformUrl($asset, $index->getTransform(), true);
                }
            } catch (Throwable) {
            }
        }
    }

    #[\Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Generating image transform');
    }
}
