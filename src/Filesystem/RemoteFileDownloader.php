<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem;

use CraftCms\UrlValidator\UrlValidationException;
use CraftCms\UrlValidator\UrlValidator;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * @since 6.0.0
 */
readonly class RemoteFileDownloader
{
    public function __construct(private UrlValidator $urls) {}

    public function download(string $url, string $path, int $maxBytes): void
    {
        $parts = parse_url($url);

        if (! filter_var($url, FILTER_VALIDATE_URL) || ! is_array($parts) || isset($parts['user']) || isset($parts['pass'])) {
            throw new RuntimeException('The file URL is invalid.');
        }

        try {
            $ips = $this->urls->validate($url);
        } catch (UrlValidationException) {
            throw new RuntimeException('The file URL must resolve to a public HTTP or HTTPS address.');
        }

        $host = $parts['host'];
        $port = $parts['port'] ?? (strtolower($parts['scheme']) === 'https' ? 443 : 80);

        try {
            $response = Http::createPendingRequest()
                ->setHandler(new CurlHandler)
                ->connectTimeout(5)
                ->timeout(60)
                ->withHeaders(['Accept-Encoding' => 'identity'])
                ->withOptions([
                    RequestOptions::ALLOW_REDIRECTS => false,
                    RequestOptions::PROXY => '',
                    RequestOptions::AUTH => null,
                    RequestOptions::COOKIES => false,
                    RequestOptions::VERIFY => true,
                    RequestOptions::DECODE_CONTENT => false,
                    RequestOptions::SINK => $path,
                    RequestOptions::ON_HEADERS => static function (ResponseInterface $response) use ($maxBytes): void {
                        if ((int) $response->getHeaderLine('Content-Length') > $maxBytes) {
                            throw new RuntimeException('The file exceeds the maximum upload size.');
                        }
                    },
                    RequestOptions::PROGRESS => static function (float $total, float $downloaded, float $uploadTotal, float $uploaded) use ($maxBytes): void {
                        if ($downloaded > $maxBytes) {
                            throw new RuntimeException('The file exceeds the maximum upload size.');
                        }
                    },
                    'curl' => [
                        CURLOPT_RESOLVE => ["$host:$port:".implode(',', array_map(
                            static fn (string $ip): string => str_contains($ip, ':') ? "[$ip]" : $ip,
                            $ips,
                        ))],
                        CURLOPT_MAXFILESIZE_LARGE => $maxBytes,
                    ],
                ])
                ->get($url);
        } catch (ConnectionException|RequestException) {
            throw new RuntimeException('The file could not be downloaded. Request a fresh file reference and try again.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('The file could not be downloaded. Redirects are not supported; provide a direct download URL.');
        }

        $size = filesize($path);

        if ($size === false || $size === 0) {
            throw new RuntimeException('The downloaded file is empty or unreadable.');
        }

        if ($size > $maxBytes) {
            throw new RuntimeException('The file exceeds the maximum upload size.');
        }
    }
}
