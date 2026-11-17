<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http;

use Illuminate\Container\Attributes\Scoped;

/**
 * @since 6.0.0
 */
#[Scoped]
class ResponseHeaders
{
    /**
     * The headers that keep browsers and shared caches from storing a response.
     */
    public const array NO_CACHE_HEADERS = [
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ];

    public private(set) bool $noCache = false;

    public private(set) ?int $duration = null;

    public private(set) bool $replace = true;

    /** @var list<array{header:string, value:string, replace:bool}> */
    public private(set) array $headers = [];

    public function noCache(): void
    {
        $this->noCache = true;
    }

    public function setCache(int $duration = 31536000, bool $replace = true): void
    {
        $this->duration = $duration;
        $this->replace = $replace;
    }

    public function add(string $header, string $value, bool $replace = true): void
    {
        $this->headers[] = [
            'header' => $header,
            'value' => $value,
            'replace' => $replace,
        ];
    }

    /**
     * Forgets everything queued so far, such as when an exception replaces the response it was queued for.
     */
    public function reset(): void
    {
        $this->noCache = false;
        $this->duration = null;
        $this->replace = true;
        $this->headers = [];
    }
}
