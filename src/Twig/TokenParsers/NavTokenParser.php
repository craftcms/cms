<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\TokenParsers;

use CraftCms\Cms\Twig\Nodes\BaseNode;
use CraftCms\Cms\Twig\Nodes\NavNode;
use Twig\Node\Expression\Variable\AssignContextVariable;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

final class NavTokenParser extends AbstractTokenParser
{
    public function getTag(): string
    {
        return 'nav';
    }

    public function parse(Token $token): NavNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $targets = $this->parseAssignmentExpression();
        $stream->expect(Token::OPERATOR_TYPE, 'in');
        $seq = $this->parser->parseExpression();
        $stream->expect(Token::BLOCK_END_TYPE);

        $upperBody = $this->parser->subparse($this->decideNavFork(...));
        $lowerBody = new BaseNode;
        $indent = new BaseNode;
        $outdent = new BaseNode;

        $nextValue = $stream->next()->getValue();

        if ($nextValue !== 'endnav') {
            $stream->expect(Token::BLOCK_END_TYPE);

            if ($nextValue === 'ifchildren') {
                $indent = $this->parser->subparse($this->decideChildrenFork(...), true);
                $stream->expect(Token::BLOCK_END_TYPE);
                $outdent = $this->parser->subparse($this->decideChildrenEnd(...), true);
                $stream->expect(Token::BLOCK_END_TYPE);
            }

            $lowerBody = $this->parser->subparse($this->decideNavEnd(...), true);
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        if (count($targets) > 1) {
            $keyTarget = $targets->getNode('0');
            $keyTarget = new AssignContextVariable($keyTarget->getAttribute('name'), $keyTarget->getTemplateLine());
            $valueTarget = $targets->getNode('1');
            $valueTarget = new AssignContextVariable($valueTarget->getAttribute('name'), $valueTarget->getTemplateLine());
        } else {
            $keyTarget = new AssignContextVariable('_key', $lineno);
            $valueTarget = $targets->getNode('0');
            $valueTarget = new AssignContextVariable($valueTarget->getAttribute('name'), $valueTarget->getTemplateLine());
        }

        return new NavNode($keyTarget, $valueTarget, $seq, $upperBody, $lowerBody, $indent, $outdent, $lineno);
    }

    public function decideNavFork(Token $token): bool
    {
        return $token->test(['ifchildren', 'children', 'endnav']);
    }

    public function decideChildrenFork(Token $token): bool
    {
        return $token->test('children');
    }

    public function decideChildrenEnd(Token $token): bool
    {
        return $token->test('endifchildren');
    }

    public function decideNavEnd(Token $token): bool
    {
        return $token->test('endnav');
    }
}
