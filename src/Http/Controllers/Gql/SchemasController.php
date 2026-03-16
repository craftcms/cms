<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Gql;

use craft\helpers\DateTimeHelper;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Flash;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class SchemasController extends GqlController
{
    use RespondsWithFlash;

    public function __construct(
        private Gql $gql,
    ) {
        $this->ensureGqlEnabled();
    }

    public function index(): View
    {
        // Ensure the public schema exists so the table stays aligned with the legacy UI.
        $this->gql->getPublicSchema();

        return view('graphql.schemas._index');
    }

    public function create(): View
    {
        return $this->renderEditSchema(new GqlSchema);
    }

    public function edit(int $schemaId): View
    {
        $schema = $this->gql->getSchemaById($schemaId);

        abort_if(is_null($schema), 404, 'Schema not found');

        return $this->renderEditSchema($schema);
    }

    public function editPublic(): View
    {
        return $this->renderEditPublicSchema(
            $this->gql->getPublicSchema(),
            $this->gql->getPublicToken(),
        );
    }

    public function save(Request $request): Response
    {
        $schemaId = $request->input('schemaId');

        if ($schemaId) {
            $schema = $this->gql->getSchemaById((int) $schemaId);

            abort_if(is_null($schema), 404, 'Schema not found');
        } else {
            $schema = new GqlSchema;
        }

        $name = $request->input('name');

        if ($name !== null) {
            $schema->name = $name;
        }

        $permissions = $request->input('permissions', []);
        $schema->scope = is_array($permissions) ? $permissions : [$permissions];

        if (! $this->gql->saveSchema($schema)) {
            return $this->invalidSchemaResponse($request, $schema, t('Couldn’t save schema.'));
        }

        return $this->asModelSuccess($schema, t('Schema saved.'), 'schema');
    }

    public function savePublic(Request $request): View|Response
    {
        $schema = $this->gql->getPublicSchema();
        $token = $this->gql->getPublicToken();

        $permissions = $request->input('permissions', []);
        $schema->scope = is_array($permissions) ? $permissions : [$permissions];

        if (! $this->gql->saveSchema($schema)) {
            return $this->invalidPublicSchemaSchemaResponse($request, $schema, $token, t('Couldn’t save schema.'));
        }

        $token->enabled = (bool) $request->input('enabled');

        if (($expiryDate = $request->input('expiryDate')) !== null) {
            $token->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }

        if (! $this->gql->saveToken($token)) {
            return $this->invalidPublicSchemaTokenResponse($request, $schema, $token, t('Couldn’t save public schema settings.'));
        }

        return $this->asSuccess(t('Schema saved.'));
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $this->gql->deleteSchemaById($request->integer('id'));

        return $this->asJsonSuccess();
    }

    private function invalidSchemaResponse(Request $request, GqlSchema $schema, string $message): Response
    {
        if ($request->expectsJson()) {
            return $this->asModelFailure($schema, $message, 'schema');
        }

        Flash::fail($message);

        return response($this->renderEditSchema($schema));
    }

    private function invalidPublicSchemaSchemaResponse(
        Request $request,
        GqlSchema $schema,
        GqlToken $token,
        string $message,
    ): Response|View {
        if ($request->expectsJson()) {
            return $this->asModelFailure($schema, $message, 'schema', [
                'token' => $token->toArray(),
            ]);
        }

        Flash::fail($message);

        return $this->renderEditPublicSchema($schema, $token);
    }

    private function invalidPublicSchemaTokenResponse(
        Request $request,
        GqlSchema $schema,
        GqlToken $token,
        string $message,
    ): View|Response {
        if ($request->expectsJson()) {
            return $this->asModelFailure($token, $message, 'token', [
                'schema' => $schema->toArray(),
            ]);
        }

        Flash::fail($message);

        return $this->renderEditPublicSchema($schema, $token);
    }

    private function renderEditSchema(GqlSchema $schema): View
    {
        $name = trim((string) $schema->name) ?: null;

        $title = $schema->id
            ? $name ?? t('Edit GraphQL Schema')
            : $name ?? t('Create a new GraphQL Schema');

        return view('graphql.schemas._edit', compact(
            'schema',
            'title',
        ));
    }

    private function renderEditPublicSchema(GqlSchema $schema, GqlToken $token): View
    {
        $title = t('Edit the public GraphQL schema');

        return view('graphql.schemas._edit', compact(
            'schema',
            'token',
            'title',
        ));
    }
}
