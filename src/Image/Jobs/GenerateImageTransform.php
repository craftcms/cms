<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Jobs;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\ImageTransformer;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Support\Facades\I18N;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Override;

class GenerateImageTransform extends Job implements ShouldBeUnique
{
    public int $uniqueFor = 300;

    public function __construct(
        public int $transformId,
        protected ?string $description = null,
    ) {
        parent::__construct();
    }

    public function handle(ImageTransformer $transformer): void
    {
        $index = $transformer->getTransformIndexModelById($this->transformId);

        if (! $index) {
            return;
        }

        if ($index->fileExists) {
            return;
        }

        /** @var Asset|null $asset */
        $asset = Asset::find()->id($index->assetId)->one();

        if (! $asset) {
            return;
        }

        $transformer->getTransformUrlForIndex($asset, $index, true);
    }

    public function uniqueId(): string
    {
        return (string) $this->transformId;
    }

    #[Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Generating image transform');
    }
}
