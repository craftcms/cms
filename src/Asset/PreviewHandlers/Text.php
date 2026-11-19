<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\PreviewHandlers;

use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;

use function CraftCms\Cms\template;

/**
 * @since 6.0.0
 */
class Text extends AssetPreviewHandler
{
    public function getPreviewHtml(array $variables = []): string
    {
        $contents = $this->asset->getContents();

        if ($this->asset->kind === FileKind::Json->value && json_validate($contents)) {
            $contents = (string) Json::encode(Json::decode($contents, false), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return template('assets/_previews/text', array_merge([
            'asset' => $this->asset,
            'language' => $this->asset->kind,
            'contents' => Html::encode($contents),
        ], $variables));
    }
}
