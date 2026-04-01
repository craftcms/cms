<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use Craft;
use CraftCms\Cms\Auth\Concerns\EnforcesPermissions;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\RouteToken\Data\RouteToken;
use CraftCms\Cms\RouteToken\RouteTokens;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Uri;

use function CraftCms\Cms\t;

readonly class PreviewController
{
    use EnforcesPermissions;

    public function createToken(Request $request, RouteTokens $tokens): JsonResponse|RedirectResponse
    {
        $tokenData = new RouteToken($request->all());
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
            route('craft.actions.preview', absolute: false), [
                'elementType' => $tokenData->elementType,
                'canonicalId' => $tokenData->getCanonicalId(),
                'siteId' => $tokenData->siteId,
                'draftId' => $tokenData->draftId ?? null,
                'revisionId' => $tokenData->revisionId ?? null,
                'userId' => $request->user()->id,
            ],
        ], token: $tokenData->previewToken);

        abort_if($token === false, 500, t('Could not create a preview token.'));

        if (isset($tokenData->redirect)) {
            return redirect($tokenData->redirect);
        }

        return new JsonResponse(compact('token'));
    }

    public function preview(Request $request, Kernel $kernel): mixed
    {
        $tokenData = new RouteToken($request->all());
        $tokenData->validate(throw: true);

        $query = $tokenData->elementType::find()
            ->siteId($tokenData->siteId)
            ->status(null);

        $elementFn = match (true) {
            ! is_null($tokenData->draftId) => fn () => $query->draftId($tokenData->draftId)->one(),
            ! is_null($tokenData->revisionId) => fn () => $query->revisionId($tokenData->revisionId)->one(),
            ! is_null($tokenData->userId) => function () use ($tokenData, $query) {
                Context::addHidden(Drafts::CONTEXT_PREVIEW_USER_ID, $tokenData->userId);

                $element = (clone $query)
                    ->draftOf($tokenData->canonicalId)
                    ->provisionalDrafts()
                    ->draftCreator($tokenData->userId)
                    ->one();

                return $element ?? $query->id($tokenData->canonicalId)->one();
            },
            default => fn () => null,
        };

        if ($element = $elementFn()) {
            if (! $element->lft && $element->getIsDerivative()) {
                // See if we can add structure data to it
                $canonical = $element->getCanonical(true);
                $element->structureId = $canonical->structureId;
                $element->root = $canonical->root;
                $element->lft = $canonical->lft;
                $element->rgt = $canonical->rgt;
                $element->level = $canonical->level;
            }

            $element->previewing = true;
            Craft::$app->getElements()->setPlaceholderElement($element);
        }

        /** @var Uri $originalUri */
        $originalUri = Context::pullHidden(HandleTokenRequest::ORIGINAL_URI_KEY);

        $response = $kernel->handle($request->duplicateWithUri(
            newUri: $originalUri->value(),
            query: $originalUri->query()->all()
        ));

        return match (true) {
            $response instanceof Response => $response->setNoCacheHeaders(),
            default => $response,
        };
    }
}
