<?php

namespace crafttests\unit\mcp;

use Mcp\Capability\Discovery\Discoverer;
use PHPUnit\Framework\TestCase;

/** Strict MCP clients, such as VS Code, reject tools whose input schemas declare arrays without items. */
class ToolSchemaTest extends TestCase
{
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
