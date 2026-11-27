<?php

declare(strict_types=1);

namespace CraftCms\Cms\Livewire;

use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\Site\Sites;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Livewire\ComponentHook;

/**
 * Records the site of components rendered on routes under the `craft` middleware, so
 * {@see ApplyCraftMiddleware} updates them with Craft’s middleware, on the same site.
 *
 * Components rendered for a preview also record its token, so their updates are preview requests too.
 * The page’s URL already has the token, and the snapshot’s checksum keeps clients from changing it.
 *
 * Components rendered on the app’s own routes aren’t marked.
 *
 * @since 6.0.0
 */
class SupportCraftSites extends ComponentHook
{
    public const string MEMO_KEY = 'craftSiteId';

    public const string PREVIEW_MEMO_KEY = 'craftPreview';

    /** The request attribute {@see ApplyCraftMiddleware} sets on updates for components it marked. */
    public const string ATTRIBUTE = 'craft.livewire';

    public function dehydrate(mixed $context): void
    {
        $request = request();

        if (! self::isCraftRequest($request)) {
            return;
        }

        $context->addMemo(self::MEMO_KEY, app(Sites::class)->getCurrentSite()->id);

        if ($request->isPreview()) {
            $context->addMemo(self::PREVIEW_MEMO_KEY, [
                'token' => Context::getHidden(HandleTokenRequest::TOKEN_KEY),
                'param' => $request->previewParam(),
            ]);
        }
    }

    private static function isCraftRequest(Request $request): bool
    {
        return $request->attributes->get(self::ATTRIBUTE) === true
            || in_array('craft', $request->route()?->gatherMiddleware() ?? [], true);
    }
}
