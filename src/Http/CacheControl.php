<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http;

use Closure;
use DateTimeInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Queues cache directives for the response to the current request, through {@see ResponseHeaders::cacheControl()}.
 *
 * Each method calls the Symfony `Response` method of the same name once the response exists, so directives
 * combine as they would on the response itself. {@see ResponseHeaders::noCache()} and
 * {@see ResponseHeaders::setCache()} replace the directives queued before them.
 *
 * @see Response
 * @since 6.0.0
 */
readonly class CacheControl
{
    /**
     * @param  Closure(Closure(Response): mixed): void  $queue
     */
    public function __construct(
        private Closure $queue,
    ) {}

    /** @see Response::setPublic() */
    public function setPublic(): static
    {
        return $this->queue(fn (Response $response) => $response->setPublic());
    }

    /** @see Response::setPrivate() */
    public function setPrivate(): static
    {
        return $this->queue(fn (Response $response) => $response->setPrivate());
    }

    /** @see Response::setImmutable() */
    public function setImmutable(bool $immutable = true): static
    {
        return $this->queue(fn (Response $response) => $response->setImmutable($immutable));
    }

    /** @see Response::setMaxAge() */
    public function setMaxAge(int $value): static
    {
        return $this->queue(fn (Response $response) => $response->setMaxAge($value));
    }

    /** @see Response::setSharedMaxAge() */
    public function setSharedMaxAge(int $value): static
    {
        return $this->queue(fn (Response $response) => $response->setSharedMaxAge($value));
    }

    /** @see Response::setStaleWhileRevalidate() */
    public function setStaleWhileRevalidate(int $value): static
    {
        return $this->queue(fn (Response $response) => $response->setStaleWhileRevalidate($value));
    }

    /** @see Response::setStaleIfError() */
    public function setStaleIfError(int $value): static
    {
        return $this->queue(fn (Response $response) => $response->setStaleIfError($value));
    }

    /** @see Response::setTtl() */
    public function setTtl(int $seconds): static
    {
        return $this->queue(fn (Response $response) => $response->setTtl($seconds));
    }

    /** @see Response::setClientTtl() */
    public function setClientTtl(int $seconds): static
    {
        return $this->queue(fn (Response $response) => $response->setClientTtl($seconds));
    }

    /** @see Response::setExpires() */
    public function setExpires(?DateTimeInterface $date): static
    {
        return $this->queue(fn (Response $response) => $response->setExpires($date));
    }

    /** @see Response::setLastModified() */
    public function setLastModified(?DateTimeInterface $date): static
    {
        return $this->queue(fn (Response $response) => $response->setLastModified($date));
    }

    /** @see Response::setEtag() */
    public function setEtag(?string $etag, bool $weak = false): static
    {
        return $this->queue(fn (Response $response) => $response->setEtag($etag, $weak));
    }

    /**
     * @param  string|string[]  $headers
     *
     * @see Response::setVary()
     */
    public function setVary(string|array $headers, bool $replace = true): static
    {
        return $this->queue(fn (Response $response) => $response->setVary($headers, $replace));
    }

    /**
     * @param  array<string, mixed>  $options
     *
     * @see Response::setCache()
     */
    public function setCache(array $options): static
    {
        // Validated now, so an unknown option fails where it was passed rather than once the response exists.
        new Response()->setCache($options);

        return $this->queue(fn (Response $response) => $response->setCache($options));
    }

    /**
     * @param  Closure(Response): mixed  $callback
     */
    private function queue(Closure $callback): static
    {
        ($this->queue)($callback);

        return $this;
    }
}
