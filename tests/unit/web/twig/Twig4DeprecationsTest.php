<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web\twig;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\models\DeprecationError;
use craft\test\TestCase;
use craft\web\View;
use Twig\Compiler;
use Twig\Extension\AbstractExtension;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use UnitTester;

/**
 * Unit tests for logging what will break in Twig 4.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class Twig4DeprecationsTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    public function testTwigDeprecationsAreLoggedWithTheTemplateLine(): void
    {
        self::assertSame("\n\n462", Craft::$app->getView()->renderString("\n\n{{ '42' ~ 1 + 41 }}" . $this->uniqueComment()));

        self::assertContains([
            'message' => 'Since twig/twig 3.15: As the "~" infix operator will change its precedence in the next major version, add explicit parentheses to avoid behavior change.',
            'line' => 3,
        ], $this->logs());
    }

    public function testCodeThatTwig4WillRejectIsLogged(): void
    {
        $node = new class(['body' => new TextNode('hi', 1)], [], 1) extends Node {
            public function compile(Compiler $compiler)
            {
                $compiler->write("ob_start();\n")->subcompile($this->getNode('body'))->write("yield strtoupper(ob_get_clean());\n");
            }
        };

        $tokenParser = new class($node) extends AbstractTokenParser {
            public function __construct(private Node $node)
            {
            }

            public function parse(Token $token)
            {
                $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);
                return $this->node;
            }

            public function getTag()
            {
                return 'shout';
            }
        };

        $view = new View();
        $view->registerTwigExtension(new class($tokenParser) extends AbstractExtension {
            public function __construct(private AbstractTokenParser $tokenParser)
            {
            }

            public function getFilters()
            {
                return [];
            }

            public function getTokenParsers(): array
            {
                return [$this->tokenParser];
            }
        });

        self::assertSame('HI', $view->renderString('{% shout %}' . $this->uniqueComment()));

        $messages = array_column($this->logs(), 'message');
        self::assertContains('`' . get_debug_type($tokenParser) . '::parse()` must declare a return type to be compatible with Twig 4.', $messages);
        self::assertContains('`' . get_debug_type($tokenParser) . '::getTag()` must declare a return type to be compatible with Twig 4.', $messages);
        self::assertContains('`' . get_debug_type($node) . '::compile()` must declare a return type to be compatible with Twig 4.', $messages);
        self::assertContains('`' . get_debug_type($node) . "` captures output with `ob_start()`, which won't capture anything on Twig 4. Wrap the nodes it captures in `Twig\\Node\\CaptureNode` instead.", $messages);
        self::assertNotEmpty(array_filter($messages, fn(string $message) => str_ends_with($message, '::getFilters()` must declare a return type to be compatible with Twig 4.')));
        self::assertEmpty(array_filter($messages, fn(string $message) => str_contains($message, '::getTokenParsers()')));

        // Anonymous class names contain a NUL byte, which PostgreSQL can't store
        Craft::$app->getDeprecator()->storeLogs();
        self::assertTrue((new Query())
            ->from(Table::DEPRECATIONERRORS)
            ->where(['key' => 'twig4-return-type:' . str_replace("\0", '', $node::class) . '::compile'])
            ->exists());
    }

    public function testDuplicateMacrosAreLoggedWithTheirTemplateLine(): void
    {
        Craft::$app->getView()->renderString("{% macro a() %}{% endmacro %}\n{% macro a() %}{% endmacro %}" . $this->uniqueComment());

        $log = array_values(array_filter(
            Craft::$app->getDeprecator()->getRequestLogs(),
            fn(DeprecationError $log) => str_contains($log->message, 'Defining the macro "a" more than once'),
        ))[0];
        self::assertStringStartsWith('__string_template__', $log->file);
        self::assertSame(2, $log->line);
    }

    public function testTemplatesThatCraftCantResolveKeepTheirName(): void
    {
        $template = Craft::$app->getView()->getTwig()->createTemplate("{{ '42' ~ 1 + 41 }}" . $this->uniqueComment(), '../preview');

        self::assertSame('462', $template->render());
        self::assertContains('../preview', array_map(fn(DeprecationError $log) => $log->file, Craft::$app->getDeprecator()->getRequestLogs()));
    }

    /**
     * Returns a comment that gives a template a source of its own, so it's compiled rather than loaded from the compiled template cache.
     */
    private function uniqueComment(): string
    {
        return '{# ' . uniqid('', true) . ' #}';
    }

    private function logs(): array
    {
        return array_values(array_map(fn(DeprecationError $log) => [
            'message' => $log->message,
            'line' => $log->line,
        ], Craft::$app->getDeprecator()->getRequestLogs()));
    }
}
