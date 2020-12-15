<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use craft\helpers\Cp;
use UnitTester;

/**
 * Unit tests for the CP Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class CpHelperTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @dataProvider fieldMethodsDataProvider
     *
     * @param string $needle
     * @param string $method
     * @param array $config
     */
    public function testFieldMethods(string $needle, string $method, array $config = [])
    {
        self::assertStringContainsString($needle, call_user_func([Cp::class, $method], $config));
    }

    /**
     * @return array
     */
    public function fieldMethodsDataProvider(): array
    {
        return [
            ['type="checkbox"', 'checkboxFieldHtml'],
            ['color-input', 'colorFieldHtml'],
            ['editable', 'editableTableFieldHtml', [
                'name' => 'test',
            ]],
            ['lightswitch', 'lightswitchFieldHtml'],
            ['<select', 'selectFieldHtml'],
            ['type="text"', 'textFieldHtml'],
            ['<textarea', 'textareaFieldHtml'],
        ];
    }
}
