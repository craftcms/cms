<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use craft\base\ElementInterface;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Http\EnforcesPermissions;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
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

    public function createToken(Tokens $tokens, Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'elementType' => ['required', 'string'],
            'canonicalId' => ['required_without:sourceId', 'int'],
            'sourceId' => ['required_without:canonicalId', 'int'],
            'siteId' => ['required', 'int'],
            'draftId' => ['nullable', 'int'],
            'revisionId' => ['nullable', 'int'],
            'previewToken' => ['nullable', 'string'],
            'redirect' => ['nullable', 'string'],
        ]);

        $canonicalId = $data['canonicalId'] ?? $data['sourceId'];

        match (true) {
            isset($data['draftId']) => $this->requirePermission("previewDraft:{$data['draftId']}"),
            isset($data['revisionId']) => $this->requirePermission("previewRevision:{$data['revisionId']}"),
            default => $this->requirePermission("previewElement:{$canonicalId}"),
        };

        $token = $tokens->createPreviewToken([
            'preview/preview', [
                'elementType' => $data['elementType'],
                'canonicalId' => (int) $canonicalId,
                'siteId' => (int) $data['siteId'],
                'draftId' => $data['draftId'] ?? null,
                'revisionId' => $data['revisionId'] ?? null,
                'userId' => $request->user()->id,
            ],
        ], token: $data['previewToken']);

        abort_if($token === false, 500, t('Could not create a preview token.'));

        if (isset($data['redirect'])) {
            return redirect($data['redirect']);
        }

        return new JsonResponse(compact('token'));
    }

    public function preview(Request $request, Kernel $kernel): mixed
    {
        $request->validate([
            'elementType' => ['required', 'string'],
            'canonicalId' => ['required', 'int'],
            'siteId' => ['required', 'int'],
            'draftId' => ['nullable', 'int'],
            'revisionId' => ['nullable', 'int'],
            'userId' => ['nullable', 'int'],
        ]);

        /** @var ElementInterface $elementType */
        $elementType = $request->input('elementType');

        $query = $elementType::find()
            ->siteId($request->integer('siteId'))
            ->status(null);

        if ($draftId = $request->integer('draftId')) {
            $element = $query
                ->draftId($draftId)
                ->one();
        } elseif ($revisionId = $request->integer('revisionId')) {
            $element = $query
                ->revisionId($revisionId)
                ->one();
        } else {
            if ($userId = $request->integer('userId')) {
                // First check if there's a provisional draft
                $user = \Craft::$app->getUsers()->getUserById($userId);
                ElementHelper::setProvisionalDraftUser($user);
                $element = (clone $query)
                    ->draftOf($request->integer('canonicalId'))
                    ->provisionalDrafts()
                    ->draftCreator($userId)
                    ->one();
            }

            $element ??= $query->id($request->integer('canonicalId'))->one();
        }

        if ($element) {
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

        Context::forgetHidden(HandleTokenRequest::TOKEN_KEY);

        /** @var \Illuminate\Support\Uri $originalUri */
        $originalUri = Context::pullHidden(HandleTokenRequest::ORIGINAL_URI_KEY);
        $originalUri = $originalUri->withoutQuery([
            'token',
            'x-craft-preview',
            'x-craft-live-preview',
        ]);

        $newRequest = $request->duplicateWithUri(
            newUri: $originalUri->value(),
            query: $originalUri->query()->all()
        );
        $newRequest->headers->remove(HandleTokenRequest::TOKEN_HEADER);
        $response = $kernel->handle($newRequest);

        return match (true) {
            $response instanceof Response => $response->setNoCacheHeaders(),
            default => $response,
        };
    }
}
