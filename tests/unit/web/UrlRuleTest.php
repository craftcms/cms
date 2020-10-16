<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web;

use Codeception\Test\Unit;
use craft\web\UrlRule;

/**
 * Unit tests for UrlRule
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UrlRuleTest extends Unit
{
    /**
     *
     */
    public function testUrlRule()
    {
        $rule = new UrlRule(['template' => 'templates/index', 'pattern' => '{handle}', 'variables' => ['2', '22']]);
        self::assertSame('templates/render', $rule->route);
        self::assertSame(['template' => 'templates/index', 'variables' => ['2', '22']], $rule->params);
    }
}
