<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use craft\services\Security;
use craft\test\TestCase;

/**
 * Unit tests for the security service
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class SecurityTest extends TestCase
{
    /**
     * @dataProvider redactIfSensitiveDataProvider
     * @param mixed $expected
     * @param string $name
     * @param mixed $value
     * @param string[] $sensitiveKeywords
     */
    public function testRedactIfSensitive(mixed $expected, string $name, mixed $value, array $sensitiveKeywords): void
    {
        $security = new Security(['sensitiveKeywords' => $sensitiveKeywords]);
        self::assertSame($expected, $security->redactIfSensitive($name, $value));
    }

    /**
     * @return array
     */
    public static function redactIfSensitiveDataProvider(): array
    {
        return [
            ['••••••••••••••••••••', 'Name', 'test stuff craft cms', []],
            ['test stuff craft cms', 'Name', 'test stuff craft cms', ['Foo']],
            ['••••••••••••••••••••', 'Name', 'test stuff craft cms', ['Name']],
            ['••••••••••••••••••••', 'Name', 'test stuff craft cms', ['Name', 'Raaaa']],
            ['••••••••••••••••••••', 'Name Addition', 'test stuff craft cms', ['Name']],
            ['••••••••••••••••••••', 'Name Addition', 'test stuff craft cms', ['Name', 'Addition']],
            ['••••••••••••••••••••', 'not', 'test stuff craft cms', ['not', 'Naaah']],
            ['test stuff craft cms', 'naah', 'test stuff craft cms', ['not', 'naaah']],
            ['••••••••••••••••••••', 'Not', 'test stuff craft cms', ['not', 'Naaah']],
            ['••••••••••••••••••••', 'not', 'test stuff craft cms', ['Not', 'Naaah']],
            ['••••••••••••••••••••', 'not naaah', 'test stuff craft cms', ['Not', 'Naaah']],
            ['••••••••••••••••••••', 'not naaah', 'test stuff craft cms', ['not', 'naaah']],
            ['••••••••••••••••••••', 'name addition', 'test stuff craft cms', ['Name', 'Addition']],
            ['test stuff craft cms', ' ', 'test stuff craft cms', ['   ']],
            ['test stuff craft cms', '😀', 'test stuff craft cms', ['😀😘']],
            ['test stuff craft cms', '😀 😘', 'test stuff craft cms', ['😀', '😘']],
            ['test stuff craft cms', '😀⛄', 'test stuff craft cms', []],
            ['not stuff craft cms', '', 'not stuff craft cms', ['not']],
            ['•••••••••••••••••••', 'NOT_STUFF_CRAFT_CMS', 'not stuff craft cms', ['NOT_STUFF']],
        ];
    }
}
