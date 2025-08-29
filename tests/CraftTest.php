<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests;

use PHPUnit\Framework\TestCase;

class CraftTest extends TestCase
{
    /**
     *
     */
    public function testParseEnv(): void
    {
        // Arrange
        putenv('CRAFT_TEST=testing');

        // Act
        $env = \CraftCms\Cms\Support\Env::parse('$CRAFT_TEST');

        // Assert
        self::assertEquals('testing', $env);
        putenv('CRAFT_TEST');
    }

    /**
     *
     */
    public function testParseEnvReturnsTrue(): void
    {
        // Arrange
        putenv('CRAFT_TEST=true');

        // Act
        $env = \CraftCms\Cms\Support\Env::parse('$CRAFT_TEST');

        // Assert
        self::assertEquals(true, $env);
        self::assertIsBool($env);
        putenv('CRAFT_TEST');
    }

    /**
     *
     */
    public function testParseEnvReturnsFalse(): void
    {
        // Arrange
        putenv('CRAFT_TEST=false');

        // Act
        $env = \CraftCms\Cms\Support\Env::parse('$CRAFT_TEST');

        // Assert
        self::assertEquals(false, $env);
        self::assertIsBool($env);
        putenv('CRAFT_TEST');
    }
}
