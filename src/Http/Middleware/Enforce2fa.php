<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Craft;
use craft\web\assets\authmethodsetup\AuthMethodSetupAsset;
use craft\web\View;
use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Http\Request;

final readonly class Enforce2fa
{
    public function __construct(
        private GeneralConfig $generalConfig,
        private Auth $auth,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->generalConfig->disable2fa) {
            return $next($request);
        }

        if (! $user = $request->user('craft')) {
            return $next($request);
        }

        /** @var \CraftCms\Cms\User\Elements\User $user */
        if ($this->auth->is2faRequired($user) && ! $this->auth->hasActiveMethod($user)) {
            Craft::$app->getView()->registerAssetBundle(AuthMethodSetupAsset::class);
            Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);

            return response()
                ->view('craftcms::_special/setup-2fa')
                ->setNoCacheHeaders();
        }

        return $next($request);
    }
}
