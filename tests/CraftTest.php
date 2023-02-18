<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests;

use Craft;
use craft\helpers\App;
use PHPUnit\Framework\TestCase;
use yii\web\Cookie;

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
        $env = App::parseEnv('$CRAFT_TEST');

        // Assert
        $this->assertEquals('testing', $env);
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
        $env = App::parseEnv('$CRAFT_TEST');

        // Assert
        $this->assertEquals(true, $env);
        $this->assertIsBool($env);
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
        $env = App::parseEnv('$CRAFT_TEST');

        // Assert
        $this->assertEquals(false, $env);
        $this->assertIsBool($env);
        putenv('CRAFT_TEST');
    }

    public function testDependencyInjection(): void
    {
        $cookie = Craft::createObject(Cookie::class);
        $cookieConfig = Craft::cookieConfig();

        foreach ($cookieConfig as $property => $value) {
            $this->assertEquals($cookie->$property, $value);
        }
    }
}
