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
    }
}
