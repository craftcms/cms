<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Translation\I18N;
use CraftCms\Cms\Updates\Updates;
use CraftCms\Cms\User\Users;
use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

final readonly class UpdateLocale
{
    public function __construct(
        private Application $app,
        private GeneralConfig $generalConfig,
        private I18N $i18N,
        private Updates $updates,
        private AuthManager $auth,
        private Users $users,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $this->app->setLocale($this->getTargetLocale($request));

        return $next($request);
    }

    private function getTargetLocale(Request $request): string
    {
        if (! Info::isInstalled()) {
            return $this->getFallbackLocale($request);
        }

        if ($this->updates->isCraftUpdatePending()) {
            return $this->getFallbackLocale($request);
        }

        if (! $request->isCpRequest()) {
            return Sites::getCurrentSite()->getLanguage();
        }

        /** @var ?\craft\elements\User $user */
        $user = $this->auth->user();

        if (
            $user?->getAuthIdentifier() &&
            ($language = $this->users->getUserPreference($user->getAuthIdentifier(), 'language')) !== null &&
            $this->i18N->validateAppLocaleId($language)
        ) {
            return $language;
        }

        return $this->generalConfig->defaultCpLanguage ?? $this->getFallbackLocale($request);
    }

    private function getFallbackLocale(Request $request): string
    {
        return $request->getPreferredLanguage($this->i18N->getAppLocaleIds()->all());
    }
}
