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
    /** @var list<array{header:string, value:string, replace:bool}> */
    public private(set) array $headers = [];

    public function add(string $header, string $value, bool $replace = true): void
    {
        $this->headers[] = [
            'header' => $header,
            'value' => $value,
            'replace' => $replace,
        ];
    }
}
