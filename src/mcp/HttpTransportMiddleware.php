<?php

declare(strict_types=1);

namespace craft\mcp;

use Craft;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use craft\services\Sites;
use Mcp\Schema\Wire\McpHeader;
use Mcp\Server\Transport\Http\Middleware\CorsMiddleware;
use Mcp\Server\Transport\Http\Middleware\DnsRebindingProtectionMiddleware;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * @since 5.12.0
 */
class HttpTransportMiddleware
{
    public function __construct(private readonly Sites $sites)
    {
    }

    /** @return list<MiddlewareInterface> */
    public function forRequest(ServerRequestInterface $request): array
    {
        [$origins, $hosts] = $this->trustedEndpoints();

        $mirroredHeaders = collect(explode(',', $request->getHeaderLine('Access-Control-Request-Headers')))
            ->map(static fn(string $header): string => trim($header))
            ->filter(static fn(string $header): bool => str_starts_with(strtolower($header), strtolower(McpHeader::PARAM_PREFIX)))
            ->all();

        return [
            new CorsMiddleware(
                allowedOrigins: $origins,
                allowedMethods: ['POST', 'DELETE', 'OPTIONS'],
                allowedHeaders: [
                    'Accept',
                    'Authorization',
                    'Content-Type',
                    StreamableHttpTransport::SESSION_HEADER,
                    McpHeader::PROTOCOL_VERSION,
                    McpHeader::METHOD,
                    McpHeader::NAME,
                    ...$mirroredHeaders,
                ],
            ),
            new DnsRebindingProtectionMiddleware($hosts),
        ];
    }

    /** @return array{list<string>, list<string>} */
    private function trustedEndpoints(): array
    {
        $urls = [UrlHelper::cpUrl()];
        $baseCpUrl = Craft::$app->getConfig()->getGeneral()->baseCpUrl;

        if (is_string($baseCpUrl)) {
            $urls[] = App::parseEnv($baseCpUrl);
        }

        if (Craft::$app->getIsInstalled()) {
            foreach ($this->sites->getAllSites(withDisabled: true) as $site) {
                $urls[] = $site->getBaseUrl();
            }
        }

        $origins = [];
        $hosts = ['localhost', '127.0.0.1', '[::1]'];

        foreach ($urls as $url) {
            if (!is_string($url) || $url === '') {
                continue;
            }

            $host = parse_url($url, PHP_URL_HOST);
            $scheme = parse_url($url, PHP_URL_SCHEME);
            $port = parse_url($url, PHP_URL_PORT);

            if (!is_string($host) || !is_string($scheme)) {
                continue;
            }

            $hosts[] = strtolower($host);
            $origins[] = strtolower($scheme) . '://' . strtolower($host) . ($port === null ? '' : ":{$port}");
        }

        return [array_values(array_unique($origins)), array_values(array_unique($hosts))];
    }
}
