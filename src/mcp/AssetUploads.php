<?php

declare(strict_types=1);

namespace craft\mcp;

use Closure;
use Craft;
use craft\elements\User;
use craft\helpers\ConfigHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use CraftCms\UrlValidator\UrlValidationException;
use CraftCms\UrlValidator\UrlValidator;
use DateInterval;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\TransferStats;
use Mcp\Exception\ToolCallException;
use Throwable;

/**
 * Turns client file references into bounded, temporary files for asset persistence.
 *
 * A file reference provides exactly one source: `download_url` (a public HTTPS URL), `data` (base64-encoded
 * contents), `path` (a local file, for the stdio server only), or `upload_id` (a file sent to an upload URL from
 * [[createUpload()]]).
 */
class AssetUploads
{
    private const MaxRedirects = 3;
    private const UploadTtl = 'PT15M';
    private const UploadIdPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    private UrlValidator $urlValidator;

    public function __construct(?UrlValidator $urlValidator = null)
    {
        $this->urlValidator = $urlValidator ?? new UrlValidator();
    }

    /**
     * @param  array<string, mixed>  $file
     * @param  Closure(string, string): mixed  $callback  Receives the temporary file path, which is deleted afterward, and the filename.
     */
    public function consumeFile(array $file, ?string $filename, Closure $callback): mixed
    {
        $sources = array_values(array_intersect(['download_url', 'data', 'path', 'upload_id'], array_keys(array_filter($file, static fn($value) => $value !== null))));

        if (count($sources) !== 1 || !is_string($file[$sources[0]]) || $file[$sources[0]] === '') {
            throw new ToolCallException('Provide a file reference with exactly one of: download_url, data, path, upload_id.');
        }

        $source = $sources[0];
        $value = $file[$source];
        $filename ??= $file['file_name'] ?? match ($source) {
            'download_url' => rawurldecode(basename((string)parse_url($value, PHP_URL_PATH))),
            'path' => basename($value),
            default => null,
        };

        $extension = $this->assertFilename($filename);
        $maxSize = ConfigHelper::sizeInBytes(Craft::$app->getConfig()->getGeneral()->maxUploadFileSize);
        $path = $this->tempPath($extension);

        try {
            match ($source) {
                'download_url' => $this->download($value, $path, $maxSize),
                'data' => $this->decode($value, $path, $maxSize),
                'path' => $this->copyLocal($value, $path, $maxSize),
                'upload_id' => $this->claimUpload($value, $path, $maxSize),
            };

            return $callback($path, $filename);
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            // Guzzle wraps exceptions thrown by request callbacks
            for ($previous = $exception->getPrevious(); $previous; $previous = $previous->getPrevious()) {
                if ($previous instanceof ToolCallException) {
                    throw $previous;
                }
            }

            throw new ToolCallException('The file could not be read or saved.', previous: $exception);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Reserves an upload for the user, returning a single-use URL that accepts the file as a raw PUT request body.
     *
     * @return array{upload_id: string, upload_url: string, method: string, expires_at: string, max_size: int}
     */
    public function createUpload(User $user, string $baseUrl): array
    {
        $this->deleteStaleUploads();

        $id = StringHelper::UUID();
        $expiresAt = DateTimeHelper::currentUTCDateTime()->add(new DateInterval(self::UploadTtl));
        $token = Craft::$app->getTokens()->createToken(['mcp/upload', ['uploadId' => $id]], 1, $expiresAt);

        if ($token === false) {
            throw new ToolCallException('Could not create an upload URL.');
        }

        FileHelper::writeToFile($this->uploadPath($id) . '.json', Json::encode(['userId' => $user->id]));

        return [
            'upload_id' => $id,
            'upload_url' => UrlHelper::urlWithToken($baseUrl, $token),
            'method' => 'PUT',
            'expires_at' => DateTimeHelper::toIso8601($expiresAt),
            'max_size' => ConfigHelper::sizeInBytes(Craft::$app->getConfig()->getGeneral()->maxUploadFileSize),
        ];
    }

    /**
     * Stores a request body for a reserved upload.
     *
     * @param  resource  $stream
     * @return int The number of bytes stored.
     */
    public function receiveUpload(string $id, $stream): int
    {
        $path = $this->uploadPath($id);

        if (!preg_match(self::UploadIdPattern, $id) || !is_file("$path.json") || file_exists($path)) {
            throw new ToolCallException('This upload URL is not valid.');
        }

        $maxSize = ConfigHelper::sizeInBytes(Craft::$app->getConfig()->getGeneral()->maxUploadFileSize);
        $target = fopen($path, 'xb');

        try {
            $size = stream_copy_to_stream($stream, $target, $maxSize + 1);
        } finally {
            fclose($target);
        }

        if ($size === false || $size > $maxSize || $size === 0) {
            unlink($path);
            throw new ToolCallException($size ? 'The file exceeds the maximum upload size.' : 'The request body was empty.');
        }

        return $size;
    }

    private function assertFilename(mixed $filename): string
    {
        if (!is_string($filename) || trim($filename) === '') {
            throw new ToolCallException('Provide filename when the file reference does not include file_name.');
        }

        if (strlen($filename) > 255 || str_contains($filename, '/')
            || str_contains($filename, '\\') || preg_match('/[\x00-\x1f\x7f]/', $filename)) {
            throw new ToolCallException('Provide a valid filename.');
        }

        $allowed = Craft::$app->getConfig()->getGeneral()->allowedFileExtensions;
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed, true)) {
            throw new ToolCallException(sprintf('The “%s” file extension is not allowed. Allowed extensions: %s.', $extension, implode(', ', $allowed)));
        }

        return $extension;
    }

    private function tempPath(string $extension): string
    {
        $path = tempnam(Craft::$app->getPath()->getTempPath(), 'mcp-upload-');

        if ($path === false || !rename($path, "$path.$extension")) {
            if ($path !== false) {
                unlink($path);
            }

            throw new ToolCallException('Could not prepare temporary upload storage.');
        }

        return "$path.$extension";
    }

    /** Downloads a public HTTPS URL, validating every redirect hop against SSRF before connecting to it. */
    private function download(string $url, string $path, int $maxSize): void
    {
        $client = null;

        for ($redirects = 0; ; $redirects++) {
            $parts = parse_url($url);

            if (!$parts || strtolower($parts['scheme'] ?? '') !== 'https'
                || isset($parts['user']) || isset($parts['pass'])
                || ($parts['port'] ?? 443) !== 443) {
                throw new ToolCallException('File references must use an HTTPS URL without credentials on port 443.');
            }

            try {
                $ips = $this->urlValidator->validate($url);
            } catch (UrlValidationException $exception) {
                throw new ToolCallException('File references must use a public hostname that resolves to public IP addresses.', previous: $exception);
            }

            $client ??= Craft::createGuzzleClient(['handler' => HandlerStack::create(new CurlHandler())]);
            $response = $client->get($url, [
                'sink' => $path,
                'allow_redirects' => false,
                'http_errors' => false,
                'timeout' => 60,
                'connect_timeout' => 10,
                'proxy' => '',
                // Pin the connection to the validated IPs so the hostname can't be re-resolved to an internal address.
                'curl' => [CURLOPT_RESOLVE => ["{$parts['host']}:443:" . implode(',', $ips)]],
                'on_stats' => function(TransferStats $stats): void {
                    $ip = $stats->getHandlerStat('primary_ip');

                    if ($ip && !$this->urlValidator->validateIp($ip)) {
                        throw new ToolCallException('File references must resolve to public IP addresses.');
                    }
                },
                'on_headers' => static function($response) use ($maxSize): void {
                    if ((int)$response->getHeaderLine('Content-Length') > $maxSize) {
                        throw new ToolCallException('The file exceeds the maximum upload size.');
                    }
                },
                'progress' => static function($downloadSize, $downloaded) use ($maxSize): void {
                    if ($downloaded > $maxSize || $downloadSize > $maxSize) {
                        throw new ToolCallException('The file exceeds the maximum upload size.');
                    }
                },
            ]);

            $status = $response->getStatusCode();

            if (in_array($status, [301, 302, 303, 307, 308], true) && $response->hasHeader('Location')) {
                if ($redirects >= self::MaxRedirects) {
                    throw new ToolCallException(sprintf('The file download redirected more than %d times.', self::MaxRedirects));
                }

                $url = (string)UriResolver::resolve(new Uri($url), new Uri($response->getHeaderLine('Location')))->withFragment('');
                continue;
            }

            if ($status !== 200) {
                throw new ToolCallException("The file download returned HTTP $status instead of 200.");
            }

            break;
        }

        $this->assertSize($path, $maxSize);
    }

    private function decode(string $data, string $path, int $maxSize): void
    {
        // Allow data URLs, e.g. `data:image/png;base64,...`
        if (str_starts_with($data, 'data:') && ($comma = strpos($data, ',')) !== false) {
            $data = substr($data, $comma + 1);
        }

        if (strlen($data) > (int)ceil($maxSize / 3) * 4 + 4) {
            throw new ToolCallException('The file exceeds the maximum upload size.');
        }

        $contents = base64_decode(preg_replace('/\s+/', '', $data), true);

        if ($contents === false || $contents === '') {
            throw new ToolCallException('The data must be base64-encoded file contents.');
        }

        file_put_contents($path, $contents);
        $this->assertSize($path, $maxSize);
    }

    private function copyLocal(string $source, string $path, int $maxSize): void
    {
        // Over HTTP, a path would refer to the web server's filesystem, not the client's.
        if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
            throw new ToolCallException('Local paths are only supported by the stdio server. Use assets_upload_url to upload a local file over HTTP.');
        }

        $isAbsolute = str_starts_with($source, '/') || preg_match('/^[a-z]:[\\\\\/]/i', $source);
        $real = $isAbsolute ? realpath($source) : false;

        if ($real === false || !is_file($real) || !is_readable($real)) {
            throw new ToolCallException('The path must be an absolute path to a readable file.');
        }

        if (filesize($real) > $maxSize) {
            throw new ToolCallException('The file exceeds the maximum upload size.');
        }

        // Copy, since saving an asset moves its temp file
        if (!copy($real, $path)) {
            throw new ToolCallException('The file could not be read.');
        }
    }

    private function claimUpload(string $id, string $path, int $maxSize): void
    {
        $upload = $this->uploadPath($id);
        $meta = preg_match(self::UploadIdPattern, $id) && is_file("$upload.json") ? Json::decode((string)file_get_contents("$upload.json")) : null;

        if (!$meta || ($meta['userId'] ?? null) !== Craft::$app->getUser()->getId()) {
            throw new ToolCallException('Upload not found. Request a new upload URL with assets_upload_url.');
        }

        if (!is_file($upload)) {
            throw new ToolCallException('Nothing has been uploaded yet. Send the file to upload_url with a PUT request first.');
        }

        // Each upload can only be used once
        unlink("$upload.json");

        if (!rename($upload, $path)) {
            throw new ToolCallException('The uploaded file could not be read.');
        }

        $this->assertSize($path, $maxSize);
    }

    private function assertSize(string $path, int $maxSize): void
    {
        clearstatcache(true, $path);

        if (filesize($path) > $maxSize) {
            throw new ToolCallException('The file exceeds the maximum upload size.');
        }
    }

    private function uploadPath(string $id): string
    {
        return Craft::$app->getPath()->getTempPath() . "/mcp-uploads/$id";
    }

    /** Deletes uploads that were never claimed. */
    private function deleteStaleUploads(): void
    {
        $dir = dirname($this->uploadPath('x'));
        $cutoff = time() - 86400;

        foreach (glob("$dir/*") ?: [] as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }
}
