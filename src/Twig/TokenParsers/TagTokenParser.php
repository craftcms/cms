<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\TokenParsers;

use CraftCms\Cms\Twig\Nodes\TagNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

final class TagTokenParser extends AbstractTokenParser
{
    public function getTag(): string
    {
        return 'tag';
    }

    public function parse(Token $token): TagNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $nodes = [
            'name' => $this->parser->parseExpression(),
        ];

        if ($stream->test(Token::NAME_TYPE, 'with')) {
            $stream->next();
            $nodes['options'] = $this->parser->parseExpression();
        }

        $stream->expect(Token::BLOCK_END_TYPE);
        $nodes['content'] = $this->parser->subparse(fn (Token $token) => $token->test('endtag'), true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new TagNode($nodes, [], $lineno);
    }
}
