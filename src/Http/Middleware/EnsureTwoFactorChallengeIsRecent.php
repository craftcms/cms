<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

use function CraftCms\Cms\t;

/**
 * Rejects 2FA challenge requests if the pending session is older than {@see TTL} seconds.
 *
 * Applied to the two-factor challenge form and both verification action routes.
 */
readonly class EnsureTwoFactorChallengeIsRecent
{
    /** Maximum number of seconds a pending 2FA session may remain open. */
    private const int TTL = 300;

    public function __construct(private GeneralConfig $generalConfig) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $pendingAt = Session::get('user.pending_2fa_at');

        if ($pendingAt && now()->timestamp - $pendingAt <= self::TTL) {
            return $next($request);
        }

        Session::forget(['user.id', 'user.login_id', 'user.remember', 'user.pending_2fa_at']);

        if ($request->wantsJson()) {
            return new JsonResponse(['message' => t('Your verification session has expired. Please sign in again.')], 419);
        }

        if ($request->isSiteRequest() && $loginPath = $this->generalConfig->getLoginPath()) {
            return redirect($loginPath);
        }

        return redirect(Url::cpUrl(CpAuthPath::Login->value));
    }
}
