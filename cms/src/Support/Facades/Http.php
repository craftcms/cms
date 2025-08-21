<?php

namespace CraftCms\Cms\Support\Facades;

use Craft;
use CraftCms\Cms\Config\GeneralConfig;
use GuzzleHttp\Utils;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Config;

/**
 * @since 6.0.0
 */
final class Http extends \Illuminate\Support\Facades\Http
{
    public static function create(array $options = []): PendingRequest
    {
        $generalConfig = app(GeneralConfig::class);

        return self::withUserAgent('Craft/'.Craft::$app->getVersion().' '.Utils::defaultUserAgent())
            ->throw()
            ->when(
                Config::has('craft.guzzle'),
                fn (PendingRequest $pendingRequest) => $pendingRequest->withOptions(Config::get('craft.guzzle')),
            )
            ->when(
                $options,
                fn (PendingRequest $pendingRequest) => $pendingRequest->withOptions($options),
            )
            ->when(
                $generalConfig->httpProxy,
                fn (PendingRequest $pendingRequest) => $pendingRequest->withOptions([
                    'proxy' => $generalConfig->httpProxy,
                ]),
            );
    }
}
