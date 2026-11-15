<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant\Uploads;

use CraftCms\Cms\Ai\Assistant\ViewableAssets;
use CraftCms\Cms\Ai\Contracts\AssistantContextTypeInterface;
use CraftCms\Cms\Ai\Data\AssistantContext;
use CraftCms\Cms\Asset\Elements\Asset;
use Illuminate\Http\Request;

/**
 * Files the user uploaded to an AI assistant conversation, which are assets they can be told to save somewhere, and
 * the tool that shows the model what’s in them.
 *
 * Items are `{type: 'craft:upload', data: {assetId}}`. Only assets uploaded to the conversation by the user count.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class UploadContextType implements AssistantContextTypeInterface
{
    public function __construct(
        private AssistantUploads $uploads,
        private ViewableAssets $viewable,
    ) {}

    public static function handle(): string
    {
        return 'craft:upload';
    }

    public function resolve(Request $request, array $items): ?AssistantContext
    {
        $user = $request->craftUser()?->asElement();
        $threadId = $request->input('threadId');

        if ($user === null || ! is_string($threadId)) {
            return null;
        }

        $assetIds = [];

        foreach ($items as $item) {
            $assetId = $item['assetId'] ?? null;

            if (is_int($assetId)) {
                $assetIds[] = $assetId;
            }
        }

        $assets = $this->uploads->assets($threadId, $user, array_values(array_unique($assetIds)));

        if ($assets === []) {
            return null;
        }

        $list = implode("\n", array_map($this->describe(...), $assets));

        return new AssistantContext(
            <<<MARKDOWN
                ### Uploaded files

                The user uploaded these files to the conversation. They’re assets, so work with them as you would other assets, by their IDs: for example, to add them to an Assets field. A temporary upload is moved to the field’s upload location when its element is saved.

                Call view_uploads to look at what’s in a file only when the user’s request needs it, such as to describe an image or summarize a document. You don’t need to look at a file to save it somewhere.

                $list
                MARKDOWN,
            [new ViewUploads($this->viewable, $threadId, $assets)],
        );
    }

    private function describe(Asset $asset): string
    {
        $details = array_filter([
            "asset ID {$asset->id}",
            $asset->kind,
            $asset->getFormattedSize(),
            $asset->getWidth() && $asset->getHeight() ? "{$asset->getWidth()}×{$asset->getHeight()}" : null,
        ]);

        $location = $asset->getVolumeId() === null
            ? 'a temporary upload'
            : "saved in the {$asset->getVolume()->name} volume";

        $view = $this->viewable->canView($asset) ? 'view_uploads can show it' : 'view_uploads can’t show it';

        return "- “{$asset->getFilename()}” (".implode(', ', $details)."): {$location}; {$view}";
    }
}
