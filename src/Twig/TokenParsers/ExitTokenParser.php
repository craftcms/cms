<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\TokenParsers;

use CraftCms\Cms\Twig\Nodes\ExitNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

final class ExitTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): ExitNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $nodes = [];

        if ($stream->test(Token::NUMBER_TYPE)) {
            $nodes['status'] = $this->parser->parseExpression();

            if (! $stream->test(Token::BLOCK_END_TYPE)) {
                $nodes['message'] = $this->parser->parseExpression();
            }
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new ExitNode($nodes, [], $lineno);
    }

    public function getTag(): string
    {
        return 'exit';
    }
}
