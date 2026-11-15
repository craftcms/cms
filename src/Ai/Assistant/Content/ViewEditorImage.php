<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant\Content;

use CraftCms\Cms\Ai\Assistant\ViewableAssets;
use CraftCms\Cms\Asset\Elements\Asset;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Files\Image;
use Laravel\Ai\Tools\Request;
use Laravel\Ai\Tools\ToolResponse;

/**
 * The AI assistant’s tool that shows its model the image of the asset open in the user’s editor. The model only looks
 * at it when a request needs it, so the image isn’t sent with every conversation about the asset.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class ViewEditorImage implements Tool
{
    public const string NAME = 'view_editor_image';

    public function __construct(
        private ViewableAssets $viewable,
        private string $conversationId,
        private Asset $asset,
    ) {}

    public function name(): string
    {
        return self::NAME;
    }

    public function description(): string
    {
        return 'Shows you the image of the asset open in the user’s editor, as its file is now. Only call it when the user’s request needs to see the image, such as to write alt text or describe it.';
    }

    public function handle(Request $request): ToolResponse
    {
        $image = $this->viewable->view($this->conversationId, $this->asset);

        return $image instanceof Image
            ? ToolResponse::make("The image of “{$this->asset->title}” is attached.", [$image])
            : ToolResponse::make("The image of “{$this->asset->title}” can’t be shown to you.");
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
