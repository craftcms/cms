<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use craft\helpers\ElementHelper;
use CraftCms\Cms\Http\EnforcesPermissions;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\Token\Data\Token;
use CraftCms\Cms\Token\Tokens;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Context;

use function CraftCms\Cms\t;

final readonly class PreviewController
{
    use EnforcesPermissions;

    public function createToken(Request $request, Tokens $tokens, Token $tokenData): JsonResponse|RedirectResponse
    {
        match (true) {
            isset($tokenData->draftId) => $this->requirePermission("previewDraft:{$tokenData->draftId}"),
            isset($tokenData->revisionId) => $this->requirePermission("previewRevision:{$tokenData->revisionId}"),
            default => $this->requirePermission("previewElement:{$tokenData->getCanonicalId()}"),
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

    public function preview(Request $request, Kernel $kernel, Token $tokenData): mixed
    {
        $query = $tokenData->elementType::find()
            ->siteId($tokenData->siteId)
            ->status(null);

        $elementFn = match (true) {
            ! is_null($tokenData->draftId) => fn () => $query->draftId($tokenData->draftId)->one(),
            ! is_null($tokenData->revisionId) => fn () => $query->revisionId($tokenData->revisionId)->one(),
            ! is_null($tokenData->userId) => function () use ($tokenData, $query) {
                ElementHelper::setProvisionalDraftUser($tokenData->userId);

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
            \Craft::$app->getElements()->setPlaceholderElement($element);
        }

        /** @var \Illuminate\Support\Uri $originalUri */
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
