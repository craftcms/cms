<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\TokenParsers;

use CraftCms\Cms\Twig\Nodes\PaginateNode;
use Twig\Node\Expression\AssignNameExpression;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

final class PaginateTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): PaginateNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $nodes = [
            'query' => $this->parser->parseExpression(),
        ];
        $stream->expect('as');
        $targets = $this->parseAssignmentExpression();
        $stream->expect(Token::BLOCK_END_TYPE);

        if (count($targets) > 1) {
            $infoVariable = $targets->getNode('0');
            $nodes['infoVariable'] = new AssignNameExpression($infoVariable->getAttribute('name'), $infoVariable->getTemplateLine());
            $resultVariable = $targets->getNode('1');
        } else {
            $nodes['infoVariable'] = new AssignNameExpression('paginate', $lineno);
            $resultVariable = $targets->getNode('0');
        }

        $nodes['resultVariable'] = new AssignNameExpression($resultVariable->getAttribute('name'), $resultVariable->getTemplateLine());

        return new PaginateNode($nodes, [], $lineno);
    }

    public function getTag(): string
    {
        return 'paginate';
    }
}
