<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web\twig;

use Craft;
use craft\test\TestCase;
use craft\web\twig\SecurityPolicy;
use craft\web\View;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use UnitTester;

/**
 * Unit tests for the SecurityPolicy class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class SecurityPolicyTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @dataProvider historicallyAllowedFunctionsDataProvider
     */
    public function testHistoricallyAllowedFunctionsAreRejected(string $function): void
    {
        $policy = new SecurityPolicy();

        self::expectException(SecurityNotAllowedFunctionError::class);
        $policy->checkSecurity([], [], [$function]);
    }

    public static function historicallyAllowedFunctionsDataProvider(): array
    {
        return [
            ['parent'],
            ['block'],
            ['attribute'],
        ];
    }

    public function testAttributeFunctionIsRejectedWithinSandbox(): void
    {
        Craft::$app->getConfig()->getGeneral()->enableTwigSandbox = true;
        $view = Craft::$app->getView();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);

        self::expectException(SecurityError::class);
        $view->renderSandboxedString('{{ attribute(_context, "foo") }}');
    }
}
