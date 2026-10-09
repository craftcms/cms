<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Gql;

use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Ui\Nodes\Table;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Data\PermissionGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
readonly class SchemasController extends GqlController
{
    use RespondsWithFlash;

    public function __construct(
        private Gql $gql,
    ) {
        $this->ensureGqlEnabled();
    }

    public function index(): CpScreenResponse
    {
        $this->gql->getPublicSchema();

        $table = Table::make('graphql-schemas')
            ->columns([
                ['key' => 'name', 'label' => t('Name')],
                ['key' => 'scope', 'label' => t('Scope')],
                ['key' => 'public', 'label' => t('Public')],
            ])
            ->rows(array_map(function (GqlSchema $schema): array {
                $scope = implode(', ', array_slice($schema->scope, 0, 2));

                if (count($schema->scope) > 2) {
                    $scope .= ' '.t('and {count} more', ['count' => count($schema->scope) - 2]);
                }

                return [
                    'id' => $schema->id,
                    'name' => [
                        'label' => $schema->name,
                        'url' => route('craft.cp.graphql.schemas.edit', ['schemaId' => $schema->isPublic ? 'public' : $schema->id]),
                    ],
                    'scope' => $scope,
                    'public' => $schema->isPublic ? t('Yes') : t('No'),
                    '_deletable' => ! $schema->isPublic,
                    ...($schema->isPublic ? [] : [
                        '_deleteUrl' => route('craft.cp.graphql.schemas.destroy', ['schemaId' => $schema->id]),
                        '_deleteConfirmMessage' => t('Are you sure you want to delete the “{name}” schema?', ['name' => $schema->name]),
                    ]),
                ];
            }, $this->gql->getSchemas()))
            ->showFooter(false)
            ->createAction(t('New schema'), route('craft.cp.graphql.schemas.create'))
            ->createActionInPageHeader()
            ->deletable();

        return new CpScreenResponse()
            ->title(t('GraphQL Schemas'))
            ->selectedSubnavItem('schemas')
            ->crumbs([
                new ActionItem()->label(t('GraphQL'))->href(route('craft.cp.graphql.schemas.index')),
                new ActionItem()->label(t('Schemas')),
            ])
            ->ui(Ui::make([$table]));
    }

    public function create(): CpScreenResponse
    {
        return $this->editScreen(new GqlSchema);
    }

    public function edit(string|int $schemaId): CpScreenResponse
    {
        [$schema, $token] = $this->resolveSchema($schemaId);

        return $this->editScreen($schema, $token);
    }

    public function store(Request $request): Response
    {
        return $this->saveSchema($request, new GqlSchema);
    }

    public function update(Request $request, string|int $schemaId): Response
    {
        [$schema, $token] = $this->resolveSchema($schemaId);

        return $this->saveSchema($request, $schema, $token);
    }

    public function destroy(Request $request, int $schemaId): Response
    {
        $schema = $this->gql->getSchemaById($schemaId);
        abort_if($schema?->isPublic, 403);

        if ($schema) {
            $this->gql->deleteSchema($schema);
        }

        return $this->asSuccess(t('Schema deleted.'));
    }

    private function saveSchema(Request $request, GqlSchema $schema, ?GqlToken $token = null): Response
    {
        if ($request->has('name')) {
            $schema->name = $request->input('name');
        }

        $permissions = $request->input('permissions', []);
        $schema->scope = is_array($permissions) ? $permissions : [$permissions];

        if (! $this->gql->saveSchema($schema)) {
            throw ValidationException::withMessages($schema->errors()->getMessages());
        }

        if (! $token) {
            return $this->asModelSuccess(
                $schema,
                t('Schema saved.'),
                'schema',
                redirect: $this->getPostedRedirectUrl($schema)
                    ?? Url::cpUrl("graphql/schemas/$schema->id"),
            );
        }

        $token->enabled = (bool) $request->input('enabled');

        if ($request->has('expiryDate')) {
            $token->expiryDate = DateTimeHelper::toDateTime($request->input('expiryDate')) ?: null;
        }

        if (! $this->gql->saveToken($token)) {
            throw ValidationException::withMessages($token->errors()->getMessages());
        }

        return $this->asSuccess(t('Schema saved.'));
    }

    /** @return array{GqlSchema, GqlToken|null} */
    private function resolveSchema(string|int $schemaId): array
    {
        if ($schemaId === 'public') {
            $schema = $this->gql->getPublicSchema();
            $token = $this->gql->getPublicToken();

            abort_if(! $schema || ! $token, 404, 'Public schema not found');

            return [$schema, $token];
        }

        $schema = $this->gql->getSchemaById((int) $schemaId);

        abort_if(is_null($schema), 404, 'Schema not found');

        return [$schema, null];
    }

    private function editScreen(GqlSchema $schema, ?GqlToken $token = null): CpScreenResponse
    {
        $title = $schema->isPublic
            ? t('Edit the public GraphQL schema')
            : ($schema->id
                ? trim((string) $schema->name) ?: t('Edit GraphQL Schema')
                : t('Create a new GraphQL Schema'));

        return new CpScreenResponse()
            ->title($title)
            ->selectedSubnavItem('schemas')
            ->crumbs([
                new ActionItem()->label(t('GraphQL Schemas'))->href(Url::cpUrl('graphql/schemas')),
                new ActionItem()->label($title),
            ])
            ->redirectUrl('graphql/schemas')
            ->inertiaPage('graphql/schemas/Edit', [
                'schema' => $schema->toArray(),
                'token' => $token ? [
                    'id' => $token->id,
                    'enabled' => $token->enabled,
                    'expiryDate' => $token->expiryDate?->format('Y-m-d\TH:i'),
                ] : null,
                'permissions' => $this->schemaPermissionGroups(),
            ]);
    }

    /** @return Collection<int, PermissionGroup> */
    private function schemaPermissionGroups(): Collection
    {
        $schemaComponents = $this->gql->getAllSchemaComponents();
        $optionalPermissions = [
            'directive:parseRefs' => [
                'label' => t('{name} directive', [
                    'name' => '@parseRefs',
                ]),
                'warning' => t('Can be exploited to reveal sensitive content by information disclosure attacks.'),
            ],
        ];

        if (! config('craft.general.disableGraphqlTransformDirective')) {
            $optionalPermissions['directive:transform'] = [
                'label' => t('{name} directive', [
                    'name' => '@transform',
                ]),
                'warning' => t('Can be exploited by DoS attacks.'),
            ];
        }

        return $this->permissionGroups($schemaComponents['queries'])
            ->merge($this->permissionGroups($schemaComponents['mutations']))
            ->push(new PermissionGroup(
                handle: 'optionalFeatures',
                heading: t('Optional Features'),
                permissions: $this->permissionList($optionalPermissions),
            ));
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $categories
     * @return Collection<int, PermissionGroup>
     */
    private function permissionGroups(array $categories): Collection
    {
        return collect($categories)
            ->filter()
            ->map(fn (array $permissions, string $heading) => new PermissionGroup(
                handle: Str::toHandle($heading),
                heading: $heading,
                permissions: $this->permissionList($permissions),
            ))
            ->values();
    }

    /**
     * @param  array<string, array<string, mixed>>  $permissions
     * @return Collection<int, Permission>
     */
    private function permissionList(array $permissions): Collection
    {
        return collect($permissions)
            ->map(fn (array $props, string $key) => new Permission(
                key: $key,
                label: $props['label'] ?? $key,
                info: $props['info'] ?? null,
                warning: $props['warning'] ?? null,
                nested: isset($props['nested'])
                    ? $this->permissionList($props['nested'])
                    : new Collection,
            ))
            ->values();
    }
}
