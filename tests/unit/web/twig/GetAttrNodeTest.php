<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web\twig;

use craft\test\TestCase;
use craft\web\twig\nodevisitors\GetAttrAdjuster;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Loader\ArrayLoader;
use Twig\Markup;
use UnitTester;

/**
 * Unit tests for the GetAttrNode class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class GetAttrNodeTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * Array keys should be normalized the same way as CoreExtension::getAttribute() within the optimized array call
     * path, which is only used when strict variables are disabled.
     *
     * @dataProvider optimizedArrayKeyDataProvider
     */
    public function testOptimizedArrayKey(string $expected, string $template, array $variables = []): void
    {
        $twig = new Environment(new ArrayLoader(['template' => $template]), [
            'cache' => false,
            'strict_variables' => false,
        ]);
        $twig->addExtension(new class() extends AbstractExtension {
            public function getNodeVisitors(): array
            {
                return [new GetAttrAdjuster()];
            }
        });

        $variables['arr'] = [0 => 'zero', '' => 'empty', 'foo' => 'bar'];
        self::assertSame($expected, $twig->render('template', $variables));
    }

    public static function optimizedArrayKeyDataProvider(): array
    {
        return [
            'constant false' => ['zero', '{{ arr[false] }}'],
            'variable false' => ['zero', '{{ arr[key] }}', ['key' => false]],
            'variable null' => ['empty', '{{ arr[key] }}', ['key' => null]],
            'constant string' => ['bar', '{{ arr["foo"] }}'],
            'stringable' => ['bar', '{{ arr[key] }}', ['key' => new Markup('foo', 'UTF-8')]],
        ];
    }
}
