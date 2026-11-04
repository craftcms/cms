<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Analysis\Jobs;

use CraftCms\Cms\Asset\Analysis\ImageAnalyzer;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Json;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Override;

/**
 * Analyzes an image with the image analysis tasks that are set up.
 *
 * @since 6.0.0
 */
class AnalyzeImage extends Job implements ShouldBeUnique
{
    public int $uniqueFor = 300;

    /**
     * @param  list<string>|null  $tasks  The tasks to run, or `null` for every image analysis task.
     */
    public function __construct(
        public int $assetId,
        public ?array $tasks = null,
        public bool $overwrite = false,
        protected ?string $description = null,
    ) {
        parent::__construct();
    }

    public function handle(ImageAnalyzer $analyzer): void
    {
        $asset = Asset::find()->id($this->assetId)->status(null)->one();

        if ($asset instanceof Asset) {
            $analyzer->analyze($asset, $this->tasks, $this->overwrite);
        }
    }

    public function uniqueId(): string
    {
        return $this->assetId.':'.md5(Json::encode([$this->tasks, $this->overwrite]));
    }

    #[Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Analyzing image');
    }
}
