<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Gql;

use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Flash;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
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

    public function index(): View
    {
        return view('graphql.tokens._index');
    }

    public function create(): View
    {
        return $this->renderEditToken(new GqlToken, accessToken: $this->gql->generateToken());
    }

    public function edit(int $tokenId): View
    {
        $token = $this->gql->getTokenById($tokenId);

        if (! $token || $token->getIsPublic()) {
            abort(404, 'Token not found');
        }

        return $this->renderEditToken($token);
    }

    public function store(Request $request): Response
    {
        $tokenId = $request->input('tokenId');

        if ($tokenId) {
            $token = $this->gql->getTokenById((int) $tokenId);

            abort_if(! $token, 404, 'Token not found');
        } else {
            $token = new GqlToken;
        }

        $name = $request->input('name');
        if ($name !== null) {
            $token->name = $name;
        }

        $accessToken = $request->input('accessToken');
        if ($accessToken !== null) {
            $token->accessToken = $accessToken;
        }

        $token->enabled = (bool) $request->input('enabled');

        $schemaId = $request->input('schema');
        $token->schemaId = is_numeric($schemaId) ? (int) $schemaId : null;

        if (($expiryDate = $request->input('expiryDate')) !== null) {
            $token->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }

        if (! $this->gql->saveToken($token)) {
            return $this->invalidTokenResponse($request, $token, t('Couldn’t save token.'));
        }

        return $this->asModelSuccess($token, t('Schema saved.'), 'token');
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $this->gql->deleteTokenById($request->integer('id'));

        return $this->asJsonSuccess();
    }

    public function fetch(Request $request): JsonResponse
    {
        abort_unless($request->expectsJson(), 400, 'Request must accept JSON in response');

        $tokenUid = $request->validate([
            'tokenUid' => ['required', 'string'],
        ])['tokenUid'];

        try {
            $token = $this->gql->getTokenByUid($tokenUid);
        } catch (InvalidArgumentException) {
            abort(400, 'Invalid schema UID.');
        }

        return new JsonResponse([
            'accessToken' => $token->accessToken,
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        abort_unless($request->expectsJson(), 400, 'Request must accept JSON in response');

        return new JsonResponse([
            'accessToken' => $this->gql->generateToken(),
        ]);
    }

    private function invalidTokenResponse(Request $request, GqlToken $token, string $message): Response
    {
        if ($request->expectsJson()) {
            return $this->asModelFailure($token, $message, 'token');
        }

        Flash::fail($message);

        return response($this->renderEditToken(
            $token,
            accessToken: $token->id ? null : ($token->accessToken ?: $this->gql->generateToken()),
        ));
    }

    private function renderEditToken(GqlToken $token, ?string $accessToken = null): View
    {
        $schemas = $this->gql->getSchemas();
        $publicSchema = $this->gql->getPublicSchema();
        $schemaOptions = [];

        foreach ($schemas as $schema) {
            if (! $publicSchema || $schema->id !== $publicSchema->id) {
                $schemaOptions[] = [
                    'label' => $schema->name,
                    'value' => $schema->id,
                ];
            }
        }

        if ($token->id && ! $token->schemaId && $schemaOptions !== []) {
            array_unshift($schemaOptions, [
                'label' => '',
                'value' => '',
            ]);
        }

        $name = trim((string) $token->name) ?: null;

        $title = $token->id
            ? $name ?? t('Edit GraphQL Token')
            : $name ?? t('Create a new GraphQL token');

        return view('graphql.tokens._edit', compact(
            'token',
            'title',
            'accessToken',
            'schemaOptions',
        ));
    }
}
