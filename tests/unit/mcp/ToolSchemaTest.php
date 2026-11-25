<?php

namespace crafttests\unit\mcp;

use Mcp\Capability\Discovery\Discoverer;
use PHPUnit\Framework\TestCase;

/** Strict MCP clients, such as VS Code, reject tools whose names contain dots or whose input schemas declare arrays without items. */
class ToolSchemaTest extends TestCase
{
    public function testCoreToolNamesAreSnakeCase(): void
    {
        $names = array_keys((new Discoverer())->discover(dirname(__DIR__, 3) . '/src', ['mcp'])->getTools());
        $this->assertNotEmpty($names);
        $this->assertSame([], array_values(array_filter($names, fn(string $name) => !preg_match('/^[a-z0-9_]{1,64}$/', $name))));
    }

    public function testEveryArrayInCoreToolInputSchemasDeclaresItems(): void
    {
        $tools = (new Discoverer())->discover(dirname(__DIR__, 3) . '/src', ['mcp'])->getTools();
        $this->assertNotEmpty($tools);

        $missing = [];
        foreach ($tools as $name => $tool) {
            $schema = json_decode(json_encode($tool->tool->inputSchema), true);
            $this->collectArraysWithoutItems($schema, $name, $missing);
        }

        $this->assertSame([], $missing);
    }

    private function collectArraysWithoutItems(array $schema, string $path, array &$missing): void
    {
        $type = $schema['type'] ?? null;
        if (($type === 'array' || (is_array($type) && in_array('array', $type, true))) && !isset($schema['items'])) {
            $missing[] = $path;
        }

        foreach ($schema as $key => $value) {
            if (is_array($value)) {
                $this->collectArraysWithoutItems($value, "$path/$key", $missing);
            }
        }
    }
}
