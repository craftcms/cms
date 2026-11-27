<?php

declare(strict_types=1);

namespace CraftCms\Cms\Livewire;

use CraftCms\Cms\Site\Sites;
use Illuminate\Http\Request;
use Livewire\ComponentHook;

/**
 * Records the site of components rendered on routes under the `craft` middleware, so
 * {@see ApplyCraftMiddleware} updates them with Craft’s middleware, on the same site.
 *
 * Components rendered on the app’s own routes aren’t marked.
 *
 * @since 6.0.0
 */
class SupportCraftSites extends ComponentHook
{
    public const string MEMO_KEY = 'craftSiteId';

    /** The request attribute {@see ApplyCraftMiddleware} sets on updates for components it marked. */
    public const string ATTRIBUTE = 'craft.livewire';

    public function dehydrate(mixed $context): void
    {
        if (self::isCraftRequest(request())) {
            $context->addMemo(self::MEMO_KEY, app(Sites::class)->getCurrentSite()->id);
        }
    }

    private static function isCraftRequest(Request $request): bool
    {
        return $request->attributes->get(self::ATTRIBUTE) === true
            || in_array('craft', $request->route()?->gatherMiddleware() ?? [], true);
    }
}
