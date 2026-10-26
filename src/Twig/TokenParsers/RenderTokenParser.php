<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\TokenParsers;

use CraftCms\Cms\Twig\Nodes\RenderNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Parses `{% render objects [with variables] [only] %}`.
 *
 * @since 6.0.0
 */
class RenderTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): RenderNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $objects = $this->parser->parseExpression();
        $variables = null;

        if ($stream->nextIf(Token::NAME_TYPE, 'with')) {
            $variables = $this->parser->parseExpression();
        }

        $only = (bool) $stream->nextIf(Token::NAME_TYPE, 'only');

        $stream->expect(Token::BLOCK_END_TYPE);

        return new RenderNode($objects, $variables, $only, false, $lineno);
    }

    public function getTag(): string
    {
        return 'render';
    }
}
