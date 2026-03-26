<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\TokenParsers;

use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Twig\Nodes\RegisterResourceNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenStream;

class RegisterResourceTokenParser extends AbstractTokenParser
{
    /**
     * @var bool Whether the tag supports a tag pair mode for capturing the JS/CSS
     */
    public bool $allowTagPair = false;

    /**
     * @var bool Whether the tag can specify the position of the resource
     */
    public bool $allowPosition = false;

    /**
     * @var bool Whether the tag can specify a runtime-based position (load/ready)
     */
    public bool $allowRuntimePosition = false;

    /**
     * @var bool Whether the tag can specify additional options
     */
    public bool $allowOptions = false;

    /**
     * @var int|null The default `$position` value that should be possed to the [[method]], if it has a `$position` argument.
     */
    public ?int $defaultPosition = null;

    /**
     * @param  string  $tag  the tag name
     * @param  string  $method  the View method the tag represents
     * @param  array  $config  name-value pairs that will be used to initialize the object properties
     */
    public function __construct(public string $tag, public string $method, array $config = [])
    {
        if (! empty($config)) {
            Typecast::configure($this, $config);
        }
    }

    public function parse(Token $token): RegisterResourceNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $nodes = [];

        // Is this a tag pair?
        if (
            $this->allowTagPair &&
            (
                $this->testPositionParam($stream) ||
                $this->testOptionsParam($stream) ||
                $stream->test(Token::BLOCK_END_TYPE)
            )
        ) {
            $capture = true;
        } else {
            $capture = false;
            $nodes['value'] = $this->parser->parseExpression();
        }

        // Is there a position param?
        if ($this->allowPosition && $stream->test(Token::NAME_TYPE, 'at')) {
            $stream->next();
            $nameToken = $stream->expect(Token::NAME_TYPE, [
                'head',
                'beginBody',
                'endBody',
                'POS_HEAD',
                'POS_BEGIN',
                'POS_END',
            ]);
            $position = $nameToken->getValue();
        } elseif ($this->allowRuntimePosition && $stream->test(Token::NAME_TYPE, 'on')) {
            $stream->next();
            $nameToken = $stream->expect(Token::NAME_TYPE, [
                'ready',
                'load',
                'POS_READY',
                'POS_LOAD',
            ]);
            $position = $nameToken->getValue();
        } else {
            $position = null;
        }

        // Is there an options param?
        if ($this->allowOptions && $stream->test(Token::NAME_TYPE, 'with')) {
            $stream->next();
            $nodes['options'] = $this->parser->parseExpression();
        }

        // Close out the tag
        $stream->expect(Token::BLOCK_END_TYPE);

        if ($capture) {
            // Tag pair. Capture the value.
            $nodes['value'] = $this->parser->subparse($this->decideBlockEnd(...), true);
            $stream->expect(Token::BLOCK_END_TYPE);
        }

        // Pass everything off to the RegisterResourceNode
        $attributes = [
            'method' => $this->method,
            'allowOptions' => $this->allowOptions,
            'allowPosition' => $this->allowPosition,
            'defaultPosition' => $this->defaultPosition,
            'capture' => $capture,
            'position' => $position,
        ];

        return new RegisterResourceNode($nodes, $attributes, $lineno);
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function decideBlockEnd(Token $token): bool
    {
        return $token->test('end'.strtolower($this->tag));
    }

    /**
     * Returns whether the next token in the stream is a position param.
     */
    private function testPositionParam(TokenStream $stream): bool
    {
        if ($this->allowPosition && $stream->test(Token::NAME_TYPE, 'at')) {
            return true;
        }

        return $this->allowRuntimePosition && $stream->test(Token::NAME_TYPE, 'on');
    }

    /**
     * Returns whether the next token in the stream is an options param.
     */
    private function testOptionsParam(TokenStream $stream): bool
    {
        return $this->allowOptions && $stream->test(Token::NAME_TYPE, 'with');
    }
}
