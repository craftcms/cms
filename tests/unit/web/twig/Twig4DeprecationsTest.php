<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web\twig;

use Craft;
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
        self::assertSame("\n\n462", Craft::$app->getView()->renderString("\n\n{{ '42' ~ 1 + 41 }}"));

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

        self::assertSame('HI', $view->renderString('{% shout %}'));

        $messages = array_column($this->logs(), 'message');
        self::assertContains('`' . $tokenParser::class . '::parse()` must declare a return type to be compatible with Twig 4.', $messages);
        self::assertContains('`' . $tokenParser::class . '::getTag()` must declare a return type to be compatible with Twig 4.', $messages);
        self::assertContains('`' . $node::class . '::compile()` must declare a return type to be compatible with Twig 4.', $messages);
        self::assertContains('`' . $node::class . "` captures output with `ob_start()`, which won't capture anything on Twig 4. Wrap the nodes it captures in `Twig\\Node\\CaptureNode` instead.", $messages);
        self::assertNotEmpty(array_filter($messages, fn(string $message) => str_ends_with($message, '::getFilters()` must declare a return type to be compatible with Twig 4.')));
        self::assertEmpty(array_filter($messages, fn(string $message) => str_contains($message, '::getTokenParsers()')));
    }

    private function logs(): array
    {
        return array_values(array_map(fn(DeprecationError $log) => [
            'message' => $log->message,
            'line' => $log->line,
        ], Craft::$app->getDeprecator()->getRequestLogs()));
    }
}
