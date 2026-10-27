<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Analysis;

use CraftCms\Cms\Asset\Analysis\Contracts\ImageAnalysisTaskInterface;
use CraftCms\Cms\Asset\Elements\Asset;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Analyzes an image for several image analysis tasks at once, with one property in its response for each.
 *
 * @internal
 *
 * @since 6.0.0
 */
class ImageAnalysisAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * @param  array<string, class-string<ImageAnalysisTaskInterface>>  $tasks  Tasks indexed by their response property.
     */
    public function __construct(
        public readonly array $tasks,
        public readonly Asset $asset,
    ) {}

    public function instructions(): string
    {
        return 'You analyze images for a content management system. Look closely at the attached image, and respond with JSON that follows the schema. Each top-level property is for one task, described in the prompt.';
    }

    /**
     * Returns the prompt, with each task’s instructions under its response property.
     */
    public function analysisPrompt(): string
    {
        $sections = [];

        foreach ($this->tasks as $property => $task) {
            $sections[] = "## $property\n\n".trim($task::instructions($this->asset));
        }

        return "Analyze the attached image for these tasks:\n\n".implode("\n\n", $sections);
    }

    public function schema(JsonSchema $schema): array
    {
        $properties = [];

        foreach ($this->tasks as $property => $task) {
            $properties[$property] = $schema->object($task::schema($schema, $this->asset))->required();
        }

        return $properties;
    }
}
