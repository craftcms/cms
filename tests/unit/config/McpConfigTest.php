<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\config;

use Codeception\Test\Unit;
use craft\config\McpConfig;
use yii\filters\RateLimiter;

class McpConfigTest extends Unit
{
    public function testFilters(): void
    {
        $filters = [
            'rateLimiter' => [
                'class' => RateLimiter::class,
                'enableRateLimitHeaders' => false,
            ],
        ];
        $config = McpConfig::create()->instructions('Custom MCP instructions');

        self::assertSame($config, $config->filters($filters));
        self::assertSame($filters, $config->filters);
        self::assertSame('Custom MCP instructions', $config->instructions);

        self::assertSame($config, $config->filters([]));
        self::assertSame([], $config->filters);
    }
}
