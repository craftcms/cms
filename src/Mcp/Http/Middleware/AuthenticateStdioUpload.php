<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Middleware;

use Closure;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use CraftCms\Cms\Mcp\AssetUploads;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

use function CraftCms\Cms\craftAuth;

/**
 * Authenticates a request to an upload session started over stdio by the session’s credential, sent as a bearer
 * token, and acts as the user who started the session, so the session’s ownership and permission checks still apply.
 *
 * @since 6.0.0
 */
readonly class AuthenticateStdioUpload
{
    public function __construct(
        private AssetUploads $uploads,
        private DatabaseManager $db,
    ) {}

    /** @param Closure(Request): mixed $next */
    public function handle(Request $request, Closure $next): mixed
    {
        $id = $request->route('upload');
        $token = $request->bearerToken();

        if (! is_string($id) || ! is_string($token) || ! hash_equals($this->uploads->credential($id), $token)) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid upload credential.');
        }

        // Every request extends the session, so even status checks write, and must see a session prepared moments ago
        $this->db->connection()->useWriteConnectionWhenReading();

        $session = UploadSession::query()->find($id, ['owner', 'expiresAt']);
        $user = $session !== null && preg_match('/^user:(\d+)$/', $session->owner, $match)
            ? User::query()->find((int) $match[1])
            : null;

        abort_unless($user instanceof User && $user->asElement()->getStatus() === UserElement::STATUS_ACTIVE, 404);
        // Some requests, such as transfer DELETE, accept expired sessions and extend them, which would outlive the credential
        abort_if($session->expiresAt->isPast(), 410, 'The upload session has expired.');

        craftAuth()->setUser($user);

        return $next($request);
    }
}
