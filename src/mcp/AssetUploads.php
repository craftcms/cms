<?php

declare(strict_types=1);

namespace craft\mcp;

use Closure;
use Craft;
use craft\helpers\ConfigHelper;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Mcp\Exception\ToolCallException;
use Throwable;

/** Downloads client file references to bounded, temporary storage. */
class AssetUploads
{
    /** @param Closure(string): mixed $callback */
    public function consumeFile(string $url, string $filename, Closure $callback): mixed
    {
        $parts = parse_url($url);

        if (!$parts || strtolower($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['fragment']) || ($parts['port'] ?? 443) !== 443) {
            throw new ToolCallException('File references must use an HTTPS URL without credentials on port 443.');
        }

        if ($filename === '' || strlen($filename) > 255 || str_contains($filename, '/')
            || str_contains($filename, '\\') || preg_match('/[\x00-\x1f\x7f]/', $filename)) {
            throw new ToolCallException('Provide a valid filename.');
        }

        $config = Craft::$app->getConfig()->getGeneral();
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($extension, $config->allowedFileExtensions, true)) {
            throw new ToolCallException('The file extension is not allowed.');
        }

        $host = trim($parts['host'], '[]');
        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : array_merge(
            array_column(dns_get_record($host, DNS_A) ?: [], 'ip'),
            array_column(dns_get_record($host, DNS_AAAA) ?: [], 'ipv6'),
        );

        if (!$addresses) {
            throw new ToolCallException('The download hostname could not be resolved.');
        }

        foreach ($addresses as $address) {
            if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
                || str_starts_with(strtolower($address), '::ffff:')) {
                throw new ToolCallException('File references must resolve to public IP addresses.');
            }
        }

        $maxSize = ConfigHelper::sizeInBytes($config->maxUploadFileSize);
        $path = tempnam(Craft::$app->getPath()->getTempPath(), 'mcp-upload-');

        if ($path === false) {
            throw new ToolCallException('Could not create temporary upload storage.');
        }

        $uploadPath = "$path.$extension";

        if (!rename($path, $uploadPath)) {
            unlink($path);
            throw new ToolCallException('Could not prepare temporary upload storage.');
        }

        $path = $uploadPath;

        try {
            $address = str_contains($addresses[0], ':') ? "[{$addresses[0]}]" : $addresses[0];
            $client = Craft::createGuzzleClient(['handler' => HandlerStack::create(new CurlHandler())]);
            $client->get($url, [
                'sink' => $path,
                'allow_redirects' => false,
                'timeout' => 60,
                'connect_timeout' => 10,
                'proxy' => '',
                'curl' => filter_var($host, FILTER_VALIDATE_IP) ? [] : [CURLOPT_RESOLVE => ["{$host}:443:{$address}"]],
                'on_headers' => static function($response) use ($maxSize): void {
                    if ($response->getStatusCode() !== 200) {
                        throw new ToolCallException('The file download did not return HTTP 200.');
                    }

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

            if (filesize($path) > $maxSize) {
                throw new ToolCallException('The file exceeds the maximum upload size.');
            }

            return $callback($path);
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new ToolCallException('The file could not be downloaded or saved.', previous: $exception);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
