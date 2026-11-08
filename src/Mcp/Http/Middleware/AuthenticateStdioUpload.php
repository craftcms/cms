<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Middleware;

use Closure;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use CraftCms\Cms\Mcp\AssetUploads;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
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
    public function __construct(private AssetUploads $uploads) {}

    /** @param Closure(Request): mixed $next */
    public function handle(Request $request, Closure $next): mixed
    {
        $id = $request->route('upload');
        $token = $request->bearerToken();

        if (! is_string($id) || ! is_string($token) || ! hash_equals($this->uploads->credential($id), $token)) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid upload credential.');
        }

        $owner = UploadSession::query()->whereKey($id)->value('owner');
        $user = is_string($owner) && preg_match('/^user:(\d+)$/', $owner, $match)
            ? User::query()->find((int) $match[1])
            : null;

        abort_unless($user instanceof User && $user->asElement()->getStatus() === UserElement::STATUS_ACTIVE, 404);

        craftAuth()->setUser($user);

        return $next($request);
    }
}
