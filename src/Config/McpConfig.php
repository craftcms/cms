<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

/**
 * @since 6.0.0
 */
class McpConfig extends BaseConfig
{
    /** @var list<string> */
    public array $middleware = [];

    /** Additional instructions appended to the full admin MCP guidance returned by info_get. */
    public string $instructions = '';

    /** @param list<string> $value */
    public function middleware(array $value): self
    {
        $this->middleware = array_values($value);

        return $this;
    }

    public function instructions(string $value): self
    {
        $this->instructions = $value;

        return $this;
    }
}
