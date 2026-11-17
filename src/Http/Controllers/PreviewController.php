<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Auth\Concerns\EnforcesPermissions;
use CraftCms\Cms\Http\Routing\ActionRoute;
use CraftCms\Cms\RouteToken\Data\RouteToken;
use CraftCms\Cms\RouteToken\Model\RouteToken as RouteTokenModel;
use CraftCms\Cms\RouteToken\RouteTokens;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
readonly class PreviewController
{
    use EnforcesPermissions;

    public function createToken(Request $request, RouteTokens $tokens): JsonResponse|RedirectResponse
    {
        // Craft 5 read these with `getParam()`, i.e. query *or* body: the View
        // links in the element editor are plain GET hrefs that mint a token and
        // redirect, so restricting this to POST left them with nothing to read.
        // Only the token's own keys are taken — a CP URL carries others (`site`)
        // that `RouteToken` rejects outright.
        $tokenData = new RouteToken(array_filter([
            'elementType' => $request->input('elementType'),
            'canonicalId' => $request->input('canonicalId'),
            'sourceId' => $request->input('sourceId'),
            'siteId' => $request->input('siteId'),
            'draftId' => $request->input('draftId'),
            'revisionId' => $request->input('revisionId'),
            'redirect' => $request->input('redirect'),
        ], fn (mixed $value): bool => $value !== null));

        if ($token = $request->input('previewToken')) {
            $tokenData->previewToken = Crypt::decrypt($token);
        }

        $tokenData->validate(throw: true);

        match (true) {
            isset($tokenData->draftId) => $this->requireSessionAuthorization("previewDraft:{$tokenData->draftId}"),
            isset($tokenData->revisionId) => $this->requireSessionAuthorization("previewRevision:{$tokenData->revisionId}"),
            default => $this->requireSessionAuthorization("previewElement:{$tokenData->getCanonicalId()}"),
        };

        $route = [
            ActionRoute::uriForSegments(['preview', 'preview'], false), [
                'elementType' => $tokenData->elementType,
                'canonicalId' => $tokenData->getCanonicalId(),
                'siteId' => $tokenData->siteId,
                'draftId' => $tokenData->draftId ?? null,
                'revisionId' => $tokenData->revisionId ?? null,
                'userId' => $request->craftUser()?->getCraftUserId(),
            ],
        ];

        $token = isset($tokenData->previewToken)
            ? $this->reusePreviewToken($tokens, $tokenData->previewToken, $route)
            : $tokens->createPreviewToken($route);

        abort_if($token === false, 500, t('Could not create a preview token.'));

        if (isset($tokenData->redirect)) {
            return redirect($tokenData->redirect);
        }

        return new JsonResponse(compact('token'));
    }

    /**
     * A pre-generated token is shared by everything on the edit screen, and
     * asked for again while it's in use, so it's created once and then kept
     * alive. Locked, as two of them can ask at once.
     *
     * @param  array<mixed>  $route
     */
    private function reusePreviewToken(RouteTokens $tokens, string $token, array $route): string|false
    {
        try {
            return Cache::lock("token:$token")->block(5, function () use ($tokens, $token, $route) {
                $existing = RouteTokenModel::where('token', $token)->first();

                if (! $existing) {
                    return $tokens->createPreviewToken($route, token: $token);
                }

                abort_unless($existing->route == $route, 400, 'Invalid preview token.');

                $existing->expiryDate = now()->addSeconds($tokens->previewTokenDuration());
                $existing->save();

                return $existing->token;
            });
        } catch (LockTimeoutException) {
            return false;
        }
    }
}
