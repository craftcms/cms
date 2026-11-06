<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem;

use CraftCms\UrlValidator\UrlValidationException;
use CraftCms\UrlValidator\UrlValidator;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * @since 6.0.0
 */
readonly class RemoteFileDownloader
{
    public function __construct(private UrlValidator $urls) {}

    /**
     * Downloads a remote file to a path, after checking its URL with the downloader’s validator, and pinning
     * the connection to the validated IP addresses so cURL can’t re-resolve the hostname to a different
     * (potentially internal) address.
     *
     * @throws RuntimeException if the URL is disallowed, or the file couldn’t be downloaded, is empty or is too large
     */
    public function download(string $url, string $path, int $maxBytes): Response
    {
        $parts = parse_url($url);

        if (! filter_var($url, FILTER_VALIDATE_URL) || ! is_array($parts) || isset($parts['user']) || isset($parts['pass'])) {
            throw new RuntimeException('The file URL is invalid.');
        }

        try {
            // validate the URL and pin its hostname/port to the validated IPs *before* opening any connection
            $curlResolve = $this->urls->curlResolve($url);
        } catch (UrlValidationException) {
            throw new RuntimeException('The file URL must resolve to a public HTTP or HTTPS address.');
        }

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
                        CURLOPT_RESOLVE => $curlResolve,
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

        return $response;
    }
}
