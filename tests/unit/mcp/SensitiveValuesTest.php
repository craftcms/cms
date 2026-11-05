<?php

namespace crafttests\unit\mcp;

use craft\mcp\SensitiveValues;
use craft\services\Security;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\TestCase;

class SensitiveValuesTest extends TestCase
{
    private SensitiveValues $values;

    protected function setUp(): void
    {
        $security = new class(['sensitiveKeywords' => ['key', 'password', 'token']]) extends Security {
            public function init(): void
            {
            }
        };

        $this->values = new SensitiveValues($security);
    }

    public function testRedactsSensitiveYamlValues(): void
    {
        $diff = implode("\n", [
            '   settings:',
            '-    apiKey: sk_live_old',
            '+    apiKey: \'$API_KEY\'',
            '     password: "hunter2"',
            '     region: us-east-1',
        ]);

        self::assertSame(implode("\n", [
            '   settings:',
            '-    apiKey: •••••••••••',
            '+    apiKey: ••••••••',
            '     password: •••••••',
            '     region: us-east-1',
        ]), $this->values->redactYaml($diff));
    }

    public function testRestoresSavedValuesForRedactedPlaceholders(): void
    {
        self::assertSame(
            ['apiKey' => 'sk_live_123', 'nested' => ['token' => 'abc'], 'region' => 'eu'],
            $this->values->restore(
                ['apiKey' => '•••', 'nested' => ['token' => '•'], 'region' => 'eu'],
                ['apiKey' => 'sk_live_123', 'nested' => ['token' => 'abc'], 'region' => 'us'],
            ),
        );

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('nested.token is redacted, so it can only keep its saved value.');
        $this->values->restore(['nested' => ['token' => '•••']], []);
    }
}
