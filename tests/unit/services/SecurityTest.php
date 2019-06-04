<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Codeception\Test\Unit;
use Craft;
use craft\services\Security;
use UnitTester;

/**
 * Unit tests for the security service
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class SecurityTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var Security
     */
    protected $security;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * @dataProvider redactIfSensitiveDataProvider
     *
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

    // Data Providers
    // =========================================================================

    /**
     * @return array
     */
    public function redactIfSensitiveDataProvider(): array
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

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();

        $this->security = Craft::$app->getSecurity();
    }
}
