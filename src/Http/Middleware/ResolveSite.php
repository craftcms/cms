<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Update\Updates;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * @since 6.0.0
 */
readonly class ResolveSite
{
    public const string HAD_SITE_TOKEN_KEY = 'craft.siteToken.hadToken';

    /**
     * The path the site is resolved from when a middleware rewrote the request URI,
     * such as an `action` param or a token route on a subpath site.
     */
    public const string REQUEST_PATH_ATTRIBUTE = 'craft.resolveSite.requestPath';

    public function __construct(
        private Sites $sites,
        private Updates $updates,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (
            Cms::isInstalled() &&
            ! $this->updates->isCraftUpdatePending() &&
            ! $request->isCpRequest()
        ) {
            $this->sites->setCurrentSite($this->resolveRequestedSite($request));
        }

        return $next($request);
    }

    private function resolveRequestedSite(Request $request): Site
    {
        if (($site = $this->siteFromToken($request)) !== null) {
            return $site;
        }

        if (($siteId = $request->header('X-Craft-Site')) !== null) {
            $site = is_numeric($siteId)
                ? $this->sites->getSiteById((int) $siteId, false)
                : $this->sites->getSiteByHandle((string) $siteId, false);

            abort_if($site === null, 400, 'Invalid site: '.$siteId);

            return $site;
        }

        return $this->siteForPath($request, (string) $request->attributes->get(self::REQUEST_PATH_ATTRIBUTE, $request->decodedPath()));
    }

    /**
     * Returns the site whose base URL best matches the request host and the given path.
     */
    public function siteForPath(Request $request, string $path): Site
    {
        return $this->sites->getAllSites(false)
            ->sortByDesc(fn (Site $site): int => $this->scoreSite($site, $request, $path))
            ->first() ?? $this->sites->getPrimarySite();
    }

    private function siteFromToken(Request $request): ?Site
    {
        $siteToken = $request->siteToken();

        if ($siteToken === null || $siteToken === '') {
            return null;
        }

        try {
            $siteId = Crypt::decrypt($siteToken);
        } catch (DecryptException) {
            abort(400, 'Invalid site token');
        }

        if (! is_numeric($siteId)) {
            abort(400, 'Invalid site token');
        }

        $site = $this->sites->getSiteById((int) $siteId, true);

        abort_if($site === null, 400, 'Invalid site ID: '.$siteId);
        Context::addHidden(self::HAD_SITE_TOKEN_KEY, true);

        return $site;
    }

    private function scoreSite(Site $site, Request $request, string $path): int
    {
        $score = $site->getBaseUrl()
            ? $this->scoreUrl($site->getBaseUrl(), $request, $path)
            : 0;

        if ($site->primary) {
            $score++;
        }

        return $score;
    }

    private function scoreUrl(string $url, Request $request, string $path): int
    {
        $parsed = parse_url($url);

        if ($parsed === false) {
            Log::info("Unable to parse the URL: $url");

            return 0;
        }

        $host = $request->getHost();

        if (
            ! empty($parsed['host']) &&
            $host !== '' &&
            strcasecmp($parsed['host'], $host) !== 0
        ) {
            return 0;
        }

        $parsedPath = trim((string) ($parsed['path'] ?? ''), '/');
        $requestPath = trim($path, '/');

        if ($parsedPath !== '' && ! str_starts_with($requestPath.'/', $parsedPath.'/')) {
            return 0;
        }

        $score = 1000 + strlen($parsedPath) * 100;
        $scheme = $request->getScheme();
        $port = $request->getPort();
        $parsedScheme = strtolower((string) ($parsed['scheme'] ?? $scheme));
        $parsedPort = (int) ($parsed['port'] ?? ($parsedScheme === 'https' ? 443 : 80));

        if ($parsedPort === $port) {
            $score += 100;
        }

        if ($parsedScheme === $scheme) {
            $score += 10;
        }

        return $score;
    }
}
