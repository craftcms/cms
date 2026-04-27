<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\LegacyAssets;

use CraftCms\Cms\View\HtmlStack;

use function CraftCms\Cms\craftAsset;

/**
 * @deprecated
 *
 * @internal
 */
class CodeMirrorAsset implements LegacyAssetInterface
{
    public array $depends = [];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('legacy/codemirror/dist/codemirror.js'));
        $htmlStack->jsFile(craftAsset('legacy/codemirror/dist/javascript.js'));
        $htmlStack->cssFile(craftAsset('legacy/codemirror/dist/codemirror.css'));
    }
}
