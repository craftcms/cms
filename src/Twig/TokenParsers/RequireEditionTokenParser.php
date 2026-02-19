<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\TokenParsers;

use CraftCms\Cms\Twig\Nodes\RequireEditionNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

final class RequireEditionTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): RequireEditionNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $nodes = [
            'editionName' => $this->parser->parseExpression(),
        ];
        $stream->expect(Token::BLOCK_END_TYPE);

        return new RequireEditionNode($nodes, [], $lineno);
    }

    public function getTag(): string
    {
        return 'requireEdition';
    }
}
