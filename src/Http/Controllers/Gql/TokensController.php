<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Gql;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Ui\Nodes\Table;
use CraftCms\Cms\Ui\Ui;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
readonly class TokensController extends GqlController
{
    use RespondsWithFlash;

    public function __construct(
        private Gql $gql,
    ) {
        $this->ensureGqlEnabled();
    }

    public function index(): CpScreenResponse
    {
        $allowDeletion = Cms::config()->allowAdminChanges;

        $table = Table::make('graphql-tokens')
            ->columns([
                ['key' => 'name', 'label' => t('Name')],
                ['key' => 'lastUsed', 'label' => t('Last Used')],
                ['key' => 'expiryDate', 'label' => t('Expires')],
            ])
            ->rows(array_map(fn (GqlToken $token): array => [
                'id' => $token->id,
                'name' => [
                    'label' => $token->name,
                    'url' => route('craft.cp.graphql.tokens.edit', ['tokenId' => $token->id]),
                ],
                'lastUsed' => $token->lastUsed ? ['date' => $token->lastUsed->format(DateTimeInterface::ATOM)] : null,
                'expiryDate' => $token->expiryDate ? ['date' => $token->expiryDate->format(DateTimeInterface::ATOM)] : null,
                ...($allowDeletion ? [
                    '_deleteUrl' => route('craft.cp.graphql.tokens.destroy', ['tokenId' => $token->id]),
                    '_deleteConfirmMessage' => t('Are you sure you want to delete the “{name}” token?', ['name' => $token->name]),
                ] : []),
            ], $this->gql->getTokens()))
            ->emptyMessage(t('No GraphQL tokens exist yet.'))
            ->createAction(t('New token'), route('craft.cp.graphql.tokens.create'))
            ->createActionInPageHeader()
            ->when($allowDeletion, fn (Table $table) => $table->deletable());

        return new CpScreenResponse()
            ->title(t('GraphQL Tokens'))
            ->selectedSubnavItem('tokens')
            ->crumbs([
                new ActionItem()->label(t('GraphQL'))->href(route('craft.cp.graphql.tokens.index')),
                new ActionItem()->label(t('Tokens')),
            ])
            ->ui(Ui::make([$table]));
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
                new ActionItem()->label(t('GraphQL Tokens'))->href(Url::cpUrl('graphql/tokens')),
                new ActionItem()->label($title),
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
