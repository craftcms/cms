<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Edition;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

final readonly class EnforceLicenses
{
    public function __construct(
        private License $license,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->user()) {
            return $next($request);
        }

        if (Edition::canTest()) {
            return $next($request);
        }

        $licenseIssues = $this->license->issues([
            LicenseKeyStatus::Trial->value,
            LicenseKeyStatus::Astray->value,
            'wrong_edition',
        ]);

        if (empty($licenseIssues)) {
            return $next($request);
        }

        $hash = $this->license->issuesHash($licenseIssues);

        if (! $this->showScreen($request, $hash)) {
            return $next($request);
        }

        $consoleUrl = rtrim(Api::craftIdEndpoint(), '/');
        $cartUrl = UrlHelper::urlWithParams("$consoleUrl/cart/new", [
            'items' => array_map(fn ($issue) => $issue[2], $licenseIssues),
        ]);

        $cookie = $request->cookie($this->license->shunCookieName());
        $data = $cookie ? Json::decode($cookie) : null;

        if (($data['hash'] ?? null) !== $hash) {
            $data = null;
        }

        $duration = match ($data['count'] ?? 0) {
            0 => 21,
            1 => 34,
            2 => 55,
            3 => 89,
            4 => 144,
            5 => 233,
            6 => 377,
            7 => 610,
            8 => 987,
            default => 1597,
        };

        return response()
            ->view('craftcms::_special/licensing-issues', [
                'issues' => $licenseIssues,
                'hash' => $hash,
                'cartUrl' => $cartUrl,
                'duration' => $duration,
            ])
            ->setStatusCode(402)
            ->setNoCacheHeaders();
    }

    private function showScreen(Request $request, string $hash): bool
    {
        $cookie = $request->cookie($this->license->shunCookieName());

        if (! $cookie) {
            return true;
        }

        // the cookie is only valid if it's for the same set of issues we're currently seeing
        $data = Json::decode($cookie);

        if ($data['hash'] !== $hash) {
            return true;
        }

        // if the cookie was created earlier today, let them pass
        return ! Date::parse($data['timestamp'])->isToday();
    }
}
