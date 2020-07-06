<?php

use PHPUnit\Framework\TestCase;

class CraftTest extends TestCase
{
    public function testParseEnv()
    {
        // Arrange
        putenv("CRAFT_TEST=testing");

        // Act
        $env = Craft::parseEnv('$CRAFT_TEST');

        // Assert
        $this->assertEquals('testing', $env);
        putenv('CRAFT_TEST');
    }

    public function testParseEnvReturnsTrue()
    {
        // Arrange
        putenv("CRAFT_TEST=true");

        // Act
        $env = Craft::parseEnv('$CRAFT_TEST');

        // Assert
        $this->assertEquals(true, $env);
        $this->assertIsBool($env);
        putenv('CRAFT_TEST');
    }

    public function testParseEnvReturnsFalse()
    {
        // Arrange
        putenv("CRAFT_TEST=false");

        // Act
        $env = Craft::parseEnv('$CRAFT_TEST');

        // Assert
        $this->assertEquals(false, $env);
        $this->assertIsBool($env);
        putenv('CRAFT_TEST');
    }
}
