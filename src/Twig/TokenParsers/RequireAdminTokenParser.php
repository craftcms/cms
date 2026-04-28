<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\TokenParsers;

use CraftCms\Cms\Twig\Nodes\RequireAdminNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class RequireAdminTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): RequireAdminNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $nodes = [];

        if (! $stream->test(Token::BLOCK_END_TYPE)) {
            $nodes['requireAdminChanges'] = $this->parser->parseExpression();
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new RequireAdminNode($nodes, [], $lineno);
    }

    public function getTag(): string
    {
        return 'requireAdmin';
    }
}
