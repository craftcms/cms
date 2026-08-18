<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http;

use Illuminate\Container\Attributes\Scoped;

#[Scoped]
class ResponseHeaders
{
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
}
