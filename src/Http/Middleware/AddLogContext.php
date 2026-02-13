<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Security;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final readonly class AddLogContext
{
    public function __construct(
        private GeneralConfig $generalConfig,
        private Security $security,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        Log::shareContext(array_filter([
            'environment' => app()->environment(),
            'userId' => $request->user()?->id,
            'sessionId' => $request->session()->getId(),
            'ips' => $this->generalConfig->storeUserIps ? $request->ips() : null,
        ]));

        if ($request->method() === 'POST') {
            Log::shareContext([
                'body' => $this->security->redactIfSensitive('', $request->input()),
            ]);
        }

        Log::info('Request received.');

        return $next($request);
    }
}
