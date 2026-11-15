<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant\Uploads;

use CraftCms\Cms\Ai\Assistant\ViewableAssets;
use CraftCms\Cms\Asset\Elements\Asset;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Files\Document;
use Laravel\Ai\Files\Image;
use Laravel\Ai\Tools\Request;
use Laravel\Ai\Tools\ToolResponse;

/**
 * The AI assistant’s tool that shows its model what’s in files the user uploaded to the conversation. The model only
 * looks at them when a request needs it, so files that are only being saved somewhere aren’t sent to it.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class ViewUploads implements Tool
{
    public const string NAME = 'view_uploads';

    /**
     * @param  array<int, Asset>  $assets  The files uploaded to the conversation, keyed by their IDs.
     */
    public function __construct(
        private ViewableAssets $viewable,
        private string $conversationId,
        private array $assets,
    ) {}

    public function name(): string
    {
        return self::NAME;
    }

    public function description(): string
    {
        return 'Shows you what’s in files the user uploaded to the conversation: images and PDFs as files, and text files as text. Only call it when the user’s request needs what’s in them.';
    }

    public function handle(Request $request): ToolResponse
    {
        $response = new ToolResponse;
        $lines = [];

        foreach (array_unique(array_map(intval(...), (array) $request['assetIds'])) as $assetId) {
            $asset = $this->assets[$assetId] ?? null;

            if ($asset === null) {
                $lines[] = "Asset ID {$assetId} isn’t a file uploaded to this conversation.";

                continue;
            }

            $name = $asset->getFilename();
            $view = $this->viewable->view($this->conversationId, $asset);

            if ($view instanceof Image || $view instanceof Document) {
                $lines[] = "“{$name}” (asset ID {$assetId}) is attached.";
                $response->attach($view);
            } elseif (is_string($view)) {
                $lines[] = "“{$name}” (asset ID {$assetId}) contains:\n<file name=\"".htmlspecialchars($name)."\">\n{$view}\n</file>";
            } else {
                $lines[] = "“{$name}” (asset ID {$assetId}) can’t be shown to you, as it’s a {$asset->kind} file.";
            }
        }

        $response->text = implode("\n\n", $lines);

        return $response;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'assetIds' => $schema->array()
                ->items($schema->integer())
                ->description('The IDs of the uploaded files to look at.')
                ->required(),
        ];
    }
}
