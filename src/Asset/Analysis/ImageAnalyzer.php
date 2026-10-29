<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Analysis;

use CraftCms\Cms\Ai\Ai;
use CraftCms\Cms\Ai\AiTasks;
use CraftCms\Cms\Ai\ModelImages;
use CraftCms\Cms\Asset\Analysis\Contracts\ImageAnalysisTaskInterface;
use CraftCms\Cms\Asset\Analysis\Data\ImageAnalysis;
use CraftCms\Cms\Asset\Analysis\Jobs\AnalyzeImage;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Image\Data\ImageColors;
use CraftCms\Cms\Image\Data\ImageMetadata;
use CraftCms\Cms\Image\Data\ImageSample;
use CraftCms\Cms\Image\Images;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

/**
 * Runs image analysis tasks for images, sampling anything else they’re missing from the same copy of their file, such
 * as their colors and embedded metadata.
 *
 * Tasks that resolve to the same provider and model share one request, so most sites send one request per image no
 * matter how many tasks they enable. The image is prepared with {@see ModelImages} first.
 *
 * @since 6.0.0
 */
#[Singleton]
class ImageAnalyzer
{
    public function __construct(
        private readonly AiTasks $aiTasks,
        private readonly Ai $ai,
        private readonly Images $images,
        private readonly ModelImages $modelImages,
        private readonly Elements $elements,
        private readonly AssetTransformers $assetTransformers,
    ) {}

    /**
     * Returns the registered image analysis tasks, indexed by handle.
     *
     * @return array<string, class-string<ImageAnalysisTaskInterface>>
     */
    public function tasks(): array
    {
        return array_filter(
            $this->aiTasks->tasksByHandle()->all(),
            static fn (string $task): bool => is_subclass_of($task, ImageAnalysisTaskInterface::class),
        );
    }

    /**
     * Returns what an image’s embedded metadata fills in for the asset in its own site, as each image analysis task
     * reads it, such as its alt text and title. This doesn’t depend on AI being set up.
     *
     * @return array<string, mixed>
     *
     * @see ImageAnalysisTaskInterface::valuesFromMetadata()
     */
    public function valuesFromMetadata(Asset $asset, ImageMetadata $metadata): array
    {
        $values = [];

        foreach ($this->tasks() as $task) {
            $values = [...$values, ...$task::valuesFromMetadata($asset, $metadata)];
        }

        return $values;
    }

    /**
     * Returns whether any image analysis task is available to run.
     */
    public function available(): bool
    {
        return array_any(array_keys($this->tasks()), $this->ai->available(...));
    }

    /**
     * Returns the handles of the tasks that would run for an image: tasks that are available and have something to do.
     *
     * @param  list<string>|null  $handles  The tasks to consider, or `null` for every image analysis task.
     * @return list<string>
     */
    public function pendingTasks(Asset $asset, ?array $handles = null, bool $overwrite = false): array
    {
        if ($asset->kind !== FileKind::Image->value) {
            return [];
        }

        $pending = [];

        foreach ($this->tasks() as $handle => $task) {
            if (
                ($handles === null || in_array($handle, $handles, true)) &&
                $this->ai->available($handle) &&
                $task::needed($asset, $overwrite)
            ) {
                $pending[] = $handle;
            }
        }

        return $pending;
    }

    /**
     * Returns whether an image is missing anything that’s read from its file: its colors, or its embedded metadata.
     *
     * Images sampled before palettes, luminance, or weights were are sampled again, unless they couldn’t be sampled at all.
     */
    public function needsSampling(Asset $asset): bool
    {
        if ($asset->kind !== FileKind::Image->value) {
            return false;
        }

        return $this->colorsNeedSampling($asset->analysis?->colors)
            || ($asset->analysis !== null && $this->hashNeedsSampling($asset->analysis))
            || $asset->analysis?->metadata === null;
    }

    /**
     * Queues the image to be analyzed, if any tasks would run for it.
     *
     * @param  list<string>|null  $handles  The tasks to run, or `null` for every image analysis task.
     * @param  bool  $sample  Whether to queue the image if it needs sampling, even if no tasks would run for it, such
     *                        as for an image that was uploaded straight to a remote filesystem and couldn’t be sampled
     *                        when it was saved.
     * @return bool Whether the image was queued.
     */
    public function queue(Asset $asset, ?array $handles = null, bool $overwrite = false, bool $sample = false): bool
    {
        if (
            ! $asset->id ||
            (! ($sample && $this->needsSampling($asset)) && $this->pendingTasks($asset, $handles, $overwrite) === [])
        ) {
            return false;
        }

        dispatch(new AnalyzeImage($asset->id, $handles, $overwrite))
            ->onQueue(Cms::config()->lowPriorityQueueName)
            ->afterCommit();

        return true;
    }

    /**
     * Analyzes an image and saves the results, sampling what it’s missing from its file first.
     *
     * Each group of tasks that share a provider and model is analyzed separately. If one group’s request fails, the
     * other groups’ results are still saved, and then the first failure is thrown.
     *
     * @param  list<string>|null  $handles  The tasks to run, or `null` for every image analysis task.
     * @return list<string> The handles of the tasks whose results were applied.
     *
     * @throws Throwable
     */
    public function analyze(Asset $asset, ?array $handles = null, bool $overwrite = false): array
    {
        [$changes, $applied, $failure] = $this->run($asset, $handles, $overwrite, sample: true);

        $this->save($asset, $changes);

        if ($failure !== null) {
            throw $failure;
        }

        return $applied;
    }

    /**
     * Analyzes an image and returns the changes the tasks would make, without saving them, such as to suggest them to
     * an editor.
     *
     * @param  list<string>|null  $handles  The tasks to run, or `null` for every image analysis task.
     *
     * @throws Throwable if any request fails
     */
    public function suggest(Asset $asset, ?array $handles = null, bool $overwrite = true): ImageAnalysisChanges
    {
        [$changes, , $failure] = $this->run($asset, $handles, $overwrite, sample: false);

        if ($failure !== null) {
            throw $failure;
        }

        return $changes;
    }

    /**
     * @param  list<string>|null  $handles
     * @return array{ImageAnalysisChanges, list<string>, Throwable|null}
     */
    private function run(Asset $asset, ?array $handles, bool $overwrite, bool $sample): array
    {
        $tasks = $this->tasks();
        $changes = new ImageAnalysisChanges;
        $applied = [];
        $failure = null;
        $copy = null;

        try {
            // Sample first, so tasks see what the image’s embedded metadata filled in, and don’t replace it
            if ($sample && $this->needsSampling($asset)) {
                $copy = $asset->getCopyOfFile();
                $this->sample($asset, $copy, $changes);
            }

            $groups = [];

            foreach ($this->pendingTasks($asset, $handles, $overwrite) as $handle) {
                $resolved = $this->ai->resolve($handle);
                $groups[$resolved->provider."\0".$resolved->model][self::responseProperty($handle)] = $tasks[$handle];
            }

            if ($groups === []) {
                return [$changes, $applied, $failure];
            }

            $copy ??= $asset->getCopyOfFile();

            $image = $this->modelImages->forFile($copy);

            foreach ($groups as $group) {
                $agent = new ImageAnalysisAgent($group, $asset);

                try {
                    $response = $this->ai->task(reset($group)::handle())->prompt($agent, $agent->analysisPrompt(), [$image]);
                } catch (Throwable $exception) {
                    Log::warning("Couldn’t analyze the image of asset {$asset->id}: {$exception->getMessage()}");
                    $failure ??= $exception;

                    continue;
                }

                $data = $response instanceof StructuredAgentResponse ? $response->toArray() : [];

                foreach ($group as $property => $task) {
                    if (is_array($data[$property] ?? null)) {
                        $task::apply($asset, $data[$property], $changes, $overwrite);
                        $applied[] = $task::handle();
                    }
                }
            }
        } finally {
            if ($copy !== null) {
                File::delete($copy);
            }
        }

        return [$changes, $applied, $failure];
    }

    /**
     * Returns a task’s property in a combined response. Handles may contain characters some providers don’t allow.
     */
    private static function responseProperty(string $handle): string
    {
        return (string) preg_replace('/[^a-zA-Z0-9_-]/', '_', $handle);
    }

    /**
     * Records what the image is missing that can be read from its file: its colors and hash, and its embedded metadata,
     * and what that fills in, as an upload’s does. What it fills in is set on the asset too, for the tasks to see.
     *
     * @see valuesFromMetadata()
     */
    private function sample(Asset $asset, string $path, ImageAnalysisChanges $changes): void
    {
        $analysis = $asset->analysis ?? new ImageAnalysis;

        if ($this->colorsNeedSampling($analysis->colors)) {
            $analysis = $analysis->withSample($this->images->sample($path));
        } elseif ($this->hashNeedsSampling($analysis)) {
            // Its colors are kept, since only its hash is missing
            $analysis = $analysis->withSample(new ImageSample($analysis->colors, $this->images->sample($path)->hash));
        }

        if ($analysis->metadata === null) {
            $metadata = $this->images->metadata($path);
            $analysis = $analysis->withMetadata($metadata);

            $values = $this->valuesFromMetadata($asset, $metadata);
            Typecast::configure($asset, $values);

            foreach ($values as $attribute => $value) {
                $changes->setForSite($asset->siteId, $attribute, $value);
            }
        }

        $changes->set('analysis', $analysis);
    }

    /**
     * Returns whether an image’s colors haven’t been sampled, or were sampled before palettes, luminance, or weights were.
     * Images that couldn’t be sampled at all have an empty grid, and aren’t sampled again.
     */
    private function colorsNeedSampling(?ImageColors $colors): bool
    {
        return $colors === null || ($colors->grid !== [] && ($colors->palette === [] || $colors->luminance === [] || $colors->weights === []));
    }

    /**
     * Returns whether an image that could be sampled was sampled before hashes were.
     */
    private function hashNeedsSampling(ImageAnalysis $analysis): bool
    {
        return $analysis->colors !== null && $analysis->colors->grid !== [] && $analysis->hash === null;
    }

    /**
     * Saves the changes: attributes for every site with the asset in its own site, and other sites’ attributes with
     * the asset in those sites.
     */
    private function save(Asset $asset, ImageAnalysisChanges $changes): void
    {
        if ($changes->isEmpty()) {
            return;
        }

        $siteAttributes = $changes->siteAttributes;
        $own = [...$changes->attributes, ...($siteAttributes[$asset->siteId] ?? [])];
        unset($siteAttributes[$asset->siteId]);

        if ($own !== []) {
            Typecast::configure($asset, $own);
            $this->elements->saveElement($asset);
        }

        // Transforms are cropped around the focal point
        if (array_key_exists('focalPoint', $changes->attributes)) {
            $this->assetTransformers->invalidate($asset);
        }

        foreach ($siteAttributes as $siteId => $attributes) {
            $siteAsset = Asset::find()->id($asset->id)->siteId($siteId)->status(null)->one();

            if ($siteAsset instanceof Asset) {
                Typecast::configure($siteAsset, $attributes);
                $this->elements->saveElement($siteAsset);
            }
        }
    }
}
