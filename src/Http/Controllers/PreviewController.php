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
            route('craft.actions.preview', absolute: false), [
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

        $elementFn = match (true) {
            (bool) $draftId = $request->integer('draftId') => fn () => $query->draftId($draftId)->one(),
            (bool) $revisionId = $request->integer('revisionId') => fn () => $query->revisionId($revisionId)->one(),
            (bool) $userId = $request->integer('userId') => function () use ($userId, $query, $request) {
                ElementHelper::setProvisionalDraftUser($userId);

                $element = (clone $query)
                    ->draftOf($request->integer('canonicalId'))
                    ->provisionalDrafts()
                    ->draftCreator($userId)
                    ->one();

                return $element ?? $query->id($request->integer('canonicalId'))->one();
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

        Context::forgetHidden(HandleTokenRequest::TOKEN_KEY);

        /** @var \Illuminate\Support\Uri $originalUri */
        $originalUri = Context::pullHidden(HandleTokenRequest::ORIGINAL_URI_KEY);

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
