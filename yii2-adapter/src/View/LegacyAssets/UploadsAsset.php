<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\View\LegacyAssets;

use CraftCms\Cms\Cp\Cp;
use CraftCms\Cms\Http\Middleware\PreventRequestForgery;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\LegacyAssets\LegacyAssetInterface;

/**
 * Loads `Craft.Uploads`, which uploads files chosen in `<input type="file" data-upload-session>` inputs through upload
 * sessions.
 *
 * @internal
 */
class UploadsAsset implements LegacyAssetInterface
{
    public array $depends = [];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->html(Cp::viteTags(['resources/js/uploads.ts']), Position::Head);

        $defaults = [
            'url' => route(request()->isCpRequest() ? 'craft.actions.craft.cp.uploads.store' : 'craft.actions.craft.uploads.store'),
        ];

        if (!PreventRequestForgery::checksOriginOnly()) {
            // Forms' own CSRF inputs are used first, since this token is stale on statically cached pages
            $defaults['csrfTokenName'] = '_token';
            $defaults['csrfToken'] = csrf_token();
        }

        // A module script, so it runs after the entry point has defined `Craft.Uploads`
        $htmlStack->script(
            sprintf('Object.assign(Craft.Uploads.defaults, %s);', Json::encode($defaults)),
            Position::BodyEnd,
            ['type' => 'module'],
        );
    }
}
