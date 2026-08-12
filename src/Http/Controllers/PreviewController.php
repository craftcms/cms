<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Auth\Concerns\EnforcesPermissions;
use CraftCms\Cms\Http\Routing\ActionRoute;
use CraftCms\Cms\RouteToken\Data\RouteToken;
use CraftCms\Cms\RouteToken\RouteTokens;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

use function CraftCms\Cms\t;

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

        $token = $tokens->createPreviewToken([
            ActionRoute::uriForSegments(['preview', 'preview'], false), [
                'elementType' => $tokenData->elementType,
                'canonicalId' => $tokenData->getCanonicalId(),
                'siteId' => $tokenData->siteId,
                'draftId' => $tokenData->draftId ?? null,
                'revisionId' => $tokenData->revisionId ?? null,
                'userId' => $request->craftUser()?->getCraftUserId(),
            ],
        ], token: $tokenData->previewToken);

        abort_if($token === false, 500, t('Could not create a preview token.'));

        if (isset($tokenData->redirect)) {
            return redirect($tokenData->redirect);
        }

        return new JsonResponse(compact('token'));
    }
}
