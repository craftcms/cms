<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Actions;

use CraftCms\Cms\Asset\Analysis\ImageAnalyzer;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Actions\ElementAction;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use Illuminate\Support\Facades\Gate;
use Override;

use function CraftCms\Cms\t;

/**
 * Queues the selected images to be analyzed by the image analysis tasks that are set up, filling in what they’re missing.
 *
 * @since 6.0.0
 */
class AnalyzeImages extends ElementAction
{
    #[Override]
    public function getTriggerLabel(): string
    {
        return t('Analyze images');
    }

    #[Override]
    public function performAction(ElementQueryInterface $query): bool
    {
        $analyzer = app(ImageAnalyzer::class);
        $queued = 0;

        foreach ($query->cursor() as $asset) {
            if ($asset instanceof Asset && Gate::check('save', $asset) && $analyzer->queue($asset)) {
                $queued++;
            }
        }

        $this->setMessage($queued === 0
            ? t('None of the selected images are missing anything AI can fill in.')
            : t('{count, plural, =1{Analyzing # image.} other{Analyzing # images.}}', ['count' => $queued]));

        return true;
    }
}
