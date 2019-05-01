<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\services;


use Codeception\Test\Unit;
use craft\services\Security;

/**
 * Unit tests for SecurityTest
 *
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class SecurityTest extends Unit
{
    /**
     * @var \UnitTester $tester
     */
    protected $tester;

    /**
     * @var Security $security
     */
    protected $security;

    public function _before()
    {
        parent::_before();

        $this->security = \Craft::$app->security;
    }

    /**
     * @dataProvider redactIfSensitiveDataProvider
     * @param $result
     * @param $name
     * @param $value
     * @param $characters
     */
    public function testRedactIfSensitive($result, $name, $value, $characters)
    {
        $this->security->sensitiveKeywords = $characters;

        $redacted = $this->security->redactIfSensitive($name, $value);
        $this->assertSame($result, $redacted);
    }
    public function redactIfSensitiveDataProvider() : array
    {
        return [
            ['••••••••••••••••••••', 'Name', 'test stuff craft cms', []],
            ['test stuff craft cms', 'Name', 'test stuff craft cms', ['Name']],

            // Capitals. Nothing done
            ['test stuff craft cms', 'Name', 'test stuff craft cms', ['Name', 'Raaaa']],
            ['test stuff craft cms', 'Name Addition', 'test stuff craft cms', ['Name']],
            ['test stuff craft cms', 'Name Addition', 'test stuff craft cms', ['Name', 'Addition']],

            // Various casing
            ['••••••••••••••••••••', 'not', 'test stuff craft cms', ['not', 'Naaah']],
            ['test stuff craft cms', 'naah', 'test stuff craft cms', ['not', 'naaah']],

            ['••••••••••••••••••••', 'Not', 'test stuff craft cms', ['not', 'Naaah']],
            ['test stuff craft cms', 'not', 'test stuff craft cms', ['Not', 'Naaah']],

            ['test stuff craft cms', 'not naaah', 'test stuff craft cms', ['Not', 'Naaah']],
            ['••••••••••••••••••••', 'not naaah', 'test stuff craft cms', ['not', 'naaah']],
            ['test stuff craft cms', 'name addition', 'test stuff craft cms', ['Name', 'Addition']],

            ['test stuff craft cms', ' ', 'test stuff craft cms', ['   ']],
            ['test stuff craft cms', '😀', 'test stuff craft cms', ['😀😘']],
            ['test stuff craft cms', '😀 😘', 'test stuff craft cms', ['😀', '😘']],

            ['test stuff craft cms', '😀⛄', 'test stuff craft cms', []],

            ['not stuff craft cms', '', 'not stuff craft cms', ['not']],
        ];
    }
}
