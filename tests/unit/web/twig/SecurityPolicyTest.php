<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web\twig;

use Craft;
use craft\base\Renderable;
use craft\elements\User;
use craft\test\TestCase;
use craft\web\twig\SecurityPolicy;
use craft\web\View;
use Illuminate\Support\Collection;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
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

    public function testRenderTagIsAllowedWithinSandbox(): void
    {
        $vars = [
            'one' => $this->renderable('a'),
            'array' => [$this->renderable('a'), $this->renderable('b')],
            'collection' => Collection::make([$this->renderable('a'), $this->renderable('b')]),
        ];

        self::assertSame('a', $this->renderSandboxedString('{% render one %}', $vars));
        self::assertSame("a\nb", $this->renderSandboxedString('{% render array %}', $vars));
        self::assertSame("a\nb", $this->renderSandboxedString('{% render collection with {foo: "bar"} only %}', $vars));
    }

    public function testRenderMethodsAreAllowedWithinSandbox(): void
    {
        $vars = [
            'one' => $this->renderable('a'),
            'collection' => Collection::make([$this->renderable('a'), $this->renderable('b')]),
            'query' => User::find()->id(-1),
        ];

        self::assertSame('a', $this->renderSandboxedString('{{ one.render() }}', $vars));
        self::assertSame("a\nb", $this->renderSandboxedString('{{ collection.render() }}', $vars));
        self::assertSame('', $this->renderSandboxedString('{{ query.render() }}', $vars));
    }

    public function testOtherCollectionMethodsAreRejectedWithinSandbox(): void
    {
        self::expectException(SecurityNotAllowedMethodError::class);
        $this->renderSandboxedString('{{ collection.toJson() }}', [
            'collection' => Collection::make([1, 2, 3]),
        ]);
    }

    private function renderSandboxedString(string $template, array $variables = []): string
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $enableTwigSandbox = $generalConfig->enableTwigSandbox;
        $generalConfig->enableTwigSandbox = true;

        try {
            return Craft::$app->getView()->renderSandboxedString($template, $variables, View::TEMPLATE_MODE_SITE);
        } finally {
            $generalConfig->enableTwigSandbox = $enableTwigSandbox;
        }
    }

    private function renderable(string $name): Renderable
    {
        return new class($name) implements Renderable {
            public function __construct(
                private string $name,
            ) {
            }

            public function render(array $variables = []): Markup
            {
                return new Markup($this->name, Craft::$app->charset);
            }
        };
    }
}
