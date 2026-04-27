<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\App;

use Carbon\CarbonInterval;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class LicensesController
{
    use RespondsWithFlash;

    public function setShunCookie(Request $request, License $license): Response
    {
        $cookieName = $license->shunCookieName();
        $oldCookie = Cookie::get($cookieName);
        $data = $oldCookie ? Json::decode($oldCookie) : [];

        $hash = $request->validate(['hash' => ['required', 'string']])['hash'];

        Cookie::queue(
            $cookieName,
            Json::encode([
                'hash' => $hash,
                'timestamp' => DateTimeHelper::toIso8601(DateTimeHelper::now()),
                'count' => ($data['count'] ?? 0) + 1,
            ]),
            CarbonInterval::year()->totalMinutes,
        );

        return $this->asSuccess();
    }
}
