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

/**
 * Returns a comment that gives a template a source of its own, so it's compiled rather than loaded from the compiled template cache.
 */
function uniqueTwigComment(): string
{
    return '{# '.uniqid('', true).' #}';
}

function twigDeprecationLogs(): array
{
    return collect(app(Deprecator::class)->getRequestLogs())
        ->map(fn ($log) => ['message' => $log->message, 'line' => $log->line])
        ->values()
        ->all();
}

it('logs Twig deprecations with the template line', function () {
    expect(renderString("\n\n{{ '42' ~ 1 + 41 }}".uniqueTwigComment()))->toBe("\n\n462");

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

    expect(renderString('{% shout %}'.uniqueTwigComment()))->toBe('HI');

    $messages = array_column(twigDeprecationLogs(), 'message');

    expect($messages)
        ->toContain('`'.get_debug_type($tokenParser).'::parse()` must declare a return type to be compatible with Twig 4.')
        ->toContain('`'.get_debug_type($tokenParser).'::getTag()` must declare a return type to be compatible with Twig 4.')
        ->toContain('`'.get_debug_type($node).'::compile()` must declare a return type to be compatible with Twig 4.')
        ->toContain('`'.get_debug_type($node)."` captures output with `ob_start()`, which won't capture anything on Twig 4. Wrap the nodes it captures in `Twig\\Node\\CaptureNode` instead.")
        ->and(collect($messages)->contains(fn (string $message) => str_ends_with($message, '::getFilters()` must declare a return type to be compatible with Twig 4.')))->toBeTrue()
        ->and(collect($messages)->contains(fn (string $message) => str_contains($message, '::getTokenParsers()')))->toBeFalse();

    // Anonymous class names contain a NUL byte, which PostgreSQL can't store
    expect(collect(app(Deprecator::class)->getRequestLogs())->pluck('key'))
        ->toContain('twig4-return-type:'.str_replace("\0", '', $node::class).'::compile');
});

it('logs duplicate macros with their template line', function () {
    renderString("{% macro a() %}{% endmacro %}\n{% macro a() %}{% endmacro %}".uniqueTwigComment());

    $log = collect(app(Deprecator::class)->getRequestLogs())
        ->first(fn ($log) => str_contains($log->message, 'Defining the macro "a" more than once'));

    expect($log->file)->not->toEndWith('.php')
        ->and($log->line)->toBe(2);
});

it('keeps the names of templates that Craft can’t resolve', function () {
    $template = Twig::get()->createTemplate("{{ '42' ~ 1 + 41 }}".uniqueTwigComment(), '../preview');

    expect($template->render())->toBe('462')
        ->and(collect(app(Deprecator::class)->getRequestLogs())->pluck('file'))->toContain('../preview');
});
