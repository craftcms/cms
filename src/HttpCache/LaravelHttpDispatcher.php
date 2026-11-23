<?php

declare(strict_types=1);

namespace CraftCms\Cms\HttpCache;

use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCache\Exception\MissingHostException;
use FOS\HttpCache\Exception\ProxyResponseException;
use FOS\HttpCache\Exception\ProxyUnreachableException;
use FOS\HttpCache\ProxyClient\Dispatcher;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Throwable;

/**
 * Sends FOSHttpCache invalidation requests through Laravel's HTTP client, so `Http::fake()` works.
 *
 * It replaces FOSHttpCache's `HttpDispatcher` and takes the same server and base URI arguments.
 *
 * @since 6.0.0
 */
class LaravelHttpDispatcher implements Dispatcher
{
    /**
     * @var array<string, RequestInterface>
     */
    private array $queue = [];

    /**
     * @var list<UriInterface>
     */
    private readonly array $servers;

    private readonly ?UriInterface $baseUri;

    /**
     * @param  list<string>  $servers  Caching proxies to send every request to, such as `['10.0.0.1:6081']`.
     *                                 Without servers, requests go to their own URL.
     * @param  string  $baseUri  The site's host and base path, for requests that invalidate a path.
     */
    public function __construct(array $servers = [], string $baseUri = '')
    {
        $this->servers = array_map($this->uri(...), $servers);
        $this->baseUri = $baseUri === '' ? null : $this->uri($baseUri);
    }

    public function invalidate(RequestInterface $invalidationRequest, bool $validateHost = true): void
    {
        if ($validateHost && $this->baseUri === null && $invalidationRequest->getUri()->getHost() === '') {
            throw MissingHostException::missingHost((string) $invalidationRequest->getUri());
        }

        $headers = $invalidationRequest->getHeaders();
        ksort($headers);

        // Unlike `HttpDispatcher`, the body is part of the key, so tag purges sent to the same API URL aren't merged.
        $key = sha1(implode("\n", [
            $invalidationRequest->getMethod(),
            (string) $invalidationRequest->getUri(),
            serialize($headers),
            (string) $invalidationRequest->getBody(),
        ]));

        $this->queue[$key] ??= $invalidationRequest;
    }

    public function flush(): int
    {
        $queue = $this->queue;
        $this->queue = [];

        if ($queue === []) {
            return 0;
        }

        $requests = array_merge(...array_map($this->fanOut(...), array_values($queue)));

        $results = Http::pool(fn (Pool $pool) => array_map(function (int $key, RequestInterface $request) use ($pool) {
            $pending = $pool->as((string) $key)->withHeaders($request->getHeaders());
            $body = (string) $request->getBody();

            // FOSHttpCache's clients send JSON bodies without a `Content-Type`.
            if ($body !== '') {
                $pending->withBody($body, $request->getHeaderLine('Content-Type') ?: 'application/json');
            }

            return $pending->send($request->getMethod(), (string) $request->getUri());
        }, array_keys($requests), $requests));

        $exceptions = new ExceptionCollection;

        foreach ($results as $result) {
            if ($result instanceof Response && $result->failed()) {
                $exceptions->add(new ProxyResponseException(sprintf(
                    '%s error response "%s" from caching proxy',
                    $result->status(),
                    $result->reason(),
                )));
            } elseif ($result instanceof Throwable) {
                $exceptions->add(new ProxyUnreachableException(
                    sprintf('Request to caching proxy failed with message "%s"', $result->getMessage()),
                    0,
                    $result,
                ));
            }
        }

        if (count($exceptions) > 0) {
            throw $exceptions;
        }

        return count($queue);
    }

    /**
     * Completes a relative request with the base URI and copies it to each server, keeping the site's `Host` header.
     *
     * @return list<RequestInterface>
     */
    private function fanOut(RequestInterface $request): array
    {
        $uri = $request->getUri();

        if ($this->baseUri !== null && $uri->getHost() === '') {
            $uri = $uri
                ->withScheme($this->baseUri->getScheme())
                ->withHost($this->baseUri->getHost())
                ->withPort($this->baseUri->getPort())
                ->withPath(rtrim($this->baseUri->getPath(), '/').'/'.ltrim($uri->getPath(), '/'));
            $request = $request->withUri($uri);
        }

        if ($this->servers === []) {
            return [$request];
        }

        return array_map(fn (UriInterface $server) => $request->withUri(
            $uri->withScheme($server->getScheme())->withHost($server->getHost())->withPort($server->getPort()),
            preserveHost: true,
        ), $this->servers);
    }

    private function uri(string $uri): UriInterface
    {
        return new Uri(str_contains($uri, '://') ? $uri : "http://$uri");
    }
}
