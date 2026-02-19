<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\TokenParsers;

use CraftCms\Cms\Twig\Nodes\RequirePermissionNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

final class RequirePermissionTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): RequirePermissionNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $nodes = [
            'permissionName' => $this->parser->parseExpression(),
        ];
        $stream->expect(Token::BLOCK_END_TYPE);

        return new RequirePermissionNode($nodes, [], $lineno);
    }

    public function getTag(): string
    {
        return 'requirePermission';
    }
}
