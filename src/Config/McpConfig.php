<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Typecast;
use InvalidArgumentException;

/**
 * @since 6.0.0
 */
class McpConfig extends BaseConfig
{
    public string $endpoint = 'mcp';

    /** @var list<string> */
    public array $middleware = [];

    /** @param array<string, mixed>|string $value */
    public static function fromConfig(array|string $value): self
    {
        $value = self::configurationArray($value);
        Typecast::properties(self::class, $value);

        $config = self::create();

        foreach ($value as $property => $propertyValue) {
            if (property_exists($config, $property) && method_exists($config, $property)) {
                $config->{$property}($propertyValue);
            }
        }

        return $config;
    }

    public function endpoint(string $value): self
    {
        $this->endpoint = $value;

        return $this;
    }

    /** @param list<string> $value */
    public function middleware(array $value): self
    {
        $this->middleware = array_values($value);

        return $this;
    }

    /**
     * @param  array<string, mixed>|string  $value
     * @return array<string, mixed>
     */
    private static function configurationArray(array|string $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = Json::decode($value);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('The MCP configuration must be an array or a JSON object.');
        }

        return $decoded;
    }
}
