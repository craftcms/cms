<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support;

use Mcp\Schema\Wire\McpHeader;

class McpRequest
{
    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public static function payload(string $method, array $params = []): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => 'mcp-test',
            'method' => $method,
            'params' => [
                ...$params,
                '_meta' => [
                    'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
                    'io.modelcontextprotocol/clientCapabilities' => (object) [],
                ],
            ],
        ];
    }

    /** @return array<string, string> */
    public static function headers(string $method, ?string $name = null): array
    {
        return array_filter([
            McpHeader::PROTOCOL_VERSION => '2026-07-28',
            McpHeader::METHOD => $method,
            McpHeader::NAME => $name,
        ], static fn (?string $value): bool => $value !== null);
    }
}
