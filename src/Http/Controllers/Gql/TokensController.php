<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Gql;

use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\Resources\GqlTokenResource;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class TokensController extends GqlController
{
    use RespondsWithFlash;

    public function __construct(
        private Gql $gql,
    ) {
        $this->ensureGqlEnabled();
    }

    public function index(): \Inertia\Response
    {
        return Inertia::render('graphql/tokens/Index', [
            'crumbs' => fn () => [
                ['label' => t('GraphQL'), 'href' => Url::cpUrl('graphql/tokens')],
                ['label' => t('Tokens')],
            ],
            'title' => t('GraphQL Tokens'),
            'tokens' => GqlTokenResource::collection($this->gql->getTokens()),
        ]);
    }

    public function create(): CpScreenResponse
    {
        return $this->editScreen(new GqlToken, accessToken: $this->gql->generateToken());
    }

    public function edit(int $tokenId): CpScreenResponse
    {
        $token = $this->gql->getTokenById($tokenId);

        abort_if(! $token || $token->getIsPublic(), 404, 'Token not found');

        return $this->editScreen($token);
    }

    public function store(Request $request): Response
    {
        return $this->saveToken($request, new GqlToken);
    }

    public function update(Request $request, int $tokenId): Response
    {
        $token = $this->gql->getTokenById($tokenId);

        abort_if(! $token || $token->getIsPublic(), 404, 'Token not found');

        return $this->saveToken($request, $token);
    }

    public function destroy(Request $request, int $tokenId): Response
    {
        $this->gql->deleteTokenById($tokenId);

        return $this->asSuccess(t('Token deleted.'));
    }

    public function accessToken(int $tokenId): JsonResponse
    {
        $token = $this->gql->getTokenById($tokenId);

        abort_if(! $token || $token->getIsPublic(), 404, 'Token not found');

        return new JsonResponse([
            'accessToken' => $token->accessToken,
        ]);
    }

    public function generate(): JsonResponse
    {
        return new JsonResponse([
            'accessToken' => $this->gql->generateToken(),
        ]);
    }

    private function saveToken(Request $request, GqlToken $token): Response
    {
        if ($request->has('name')) {
            $token->name = $request->input('name');
        }

        if ($request->filled('accessToken')) {
            $token->accessToken = $request->input('accessToken');
        }

        $token->enabled = (bool) $request->input('enabled');

        $schemaId = $request->input('schema');
        $token->schemaId = is_numeric($schemaId) ? (int) $schemaId : null;

        if ($request->has('expiryDate')) {
            $token->expiryDate = DateTimeHelper::toDateTime($request->input('expiryDate')) ?: null;
        }

        if (! $this->gql->saveToken($token)) {
            throw ValidationException::withMessages($token->errors()->getMessages());
        }

        return $this->asModelSuccess(
            $token,
            t('Token saved.'),
            'token',
            redirect: $this->getPostedRedirectUrl($token)
                ?? Url::cpUrl("graphql/tokens/$token->id"),
        );
    }

    private function editScreen(GqlToken $token, ?string $accessToken = null): CpScreenResponse
    {
        $name = trim((string) $token->name) ?: null;

        $title = $token->id
            ? $name ?? t('Edit GraphQL Token')
            : $name ?? t('Create a new GraphQL token');

        return new CpScreenResponse()
            ->title($title)
            ->selectedSubnavItem('tokens')
            ->crumbs([
                ['label' => t('GraphQL Tokens'), 'href' => Url::cpUrl('graphql/tokens')],
                ['label' => $title],
            ])
            ->redirectUrl('graphql/tokens')
            ->inertiaPage('graphql/tokens/Edit', [
                'token' => $this->tokenData($token),
                'accessToken' => $accessToken,
                'schemaOptions' => $this->schemaOptions($token),
            ]);
    }

    /** @return array<string, bool|int|string|null> */
    private function tokenData(GqlToken $token): array
    {
        return [
            'id' => $token->id,
            'uid' => $token->uid,
            'name' => $token->name,
            'schemaId' => $token->schemaId,
            'enabled' => $token->enabled,
            'expiryDate' => $token->expiryDate?->format('Y-m-d\TH:i'),
        ];
    }

    /** @return list<array{label:string|null, value:string}> */
    private function schemaOptions(GqlToken $token): array
    {
        $publicSchema = $this->gql->getPublicSchema();
        $schemaOptions = [];

        foreach ($this->gql->getSchemas() as $schema) {
            if ($publicSchema && $schema->id === $publicSchema->id) {
                continue;
            }

            $schemaOptions[] = [
                'label' => $schema->name,
                'value' => (string) $schema->id,
            ];
        }

        if ($token->id && ! $token->schemaId && $schemaOptions !== []) {
            array_unshift($schemaOptions, [
                'label' => '',
                'value' => '',
            ]);
        }

        return $schemaOptions;
    }
}
