<?php

declare(strict_types=1);

use CraftCms\Cms\Deprecator\Deprecator;
use CraftCms\Cms\Support\Facades\Twig;
use Twig\Compiler;
use Twig\Extension\AbstractExtension;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

use function CraftCms\Cms\renderString;

beforeEach(function () {
    Deprecator::$logTarget = 'db';
    Deprecator::$throwExceptions = false;
});

function twigDeprecationLogs(): array
{
    return collect(app(Deprecator::class)->getRequestLogs())
        ->map(fn ($log) => ['message' => $log->message, 'line' => $log->line])
        ->values()
        ->all();
}

it('logs Twig deprecations with the template line', function () {
    expect(renderString("\n\n{{ '42' ~ 1 + 41 }}"))->toBe("\n\n462");

    expect(twigDeprecationLogs())->toBe([[
        'message' => 'Since twig/twig 3.15: As the "~" infix operator will change its precedence in the next major version, add explicit parentheses to avoid behavior change.',
        'line' => 3,
    ]]);
});

it('logs extensions, token parsers, and nodes that Twig 4 will reject', function () {
    $node = new class(['body' => new TextNode('hi', 1)], [], 1) extends Node
    {
        public function compile(Compiler $compiler)
        {
            $compiler->write("ob_start();\n")->subcompile($this->getNode('body'))->write("yield strtoupper(ob_get_clean());\n");
        }
    };

    $tokenParser = new class($node) extends AbstractTokenParser
    {
        public function __construct(private readonly Node $node) {}

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

    Twig::registerExtension(new class($tokenParser) extends AbstractExtension
    {
        public function __construct(private readonly AbstractTokenParser $tokenParser) {}

        public function getFilters()
        {
            return [];
        }

        public function getTokenParsers(): array
        {
            return [$this->tokenParser];
        }
    });

    expect(renderString('{% shout %}'))->toBe('HI');

    $messages = array_column(twigDeprecationLogs(), 'message');

    expect($messages)
        ->toContain('`'.$tokenParser::class.'::parse()` must declare a return type to be compatible with Twig 4.')
        ->toContain('`'.$tokenParser::class.'::getTag()` must declare a return type to be compatible with Twig 4.')
        ->toContain('`'.$node::class.'::compile()` must declare a return type to be compatible with Twig 4.')
        ->toContain('`'.$node::class."` captures output with `ob_start()`, which won't capture anything on Twig 4. Wrap the nodes it captures in `Twig\\Node\\CaptureNode` instead.")
        ->and(collect($messages)->contains(fn (string $message) => str_ends_with($message, '::getFilters()` must declare a return type to be compatible with Twig 4.')))->toBeTrue()
        ->and(collect($messages)->contains(fn (string $message) => str_contains($message, '::getTokenParsers()')))->toBeFalse();
});
