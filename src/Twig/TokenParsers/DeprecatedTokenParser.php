<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\TokenParsers;

use CraftCms\Cms\Twig\Nodes\DeprecatedNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Deprecates a section of a template.
 *
 * ```twig
 * {% deprecated 'The "base.twig" template is deprecated, use "layout.twig" instead.' %}
 * {% extends 'layout.html.twig' %}
 * ```
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class DeprecatedTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): DeprecatedNode
    {
        $expr = $this->parser->parseExpression();

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new DeprecatedNode($expr, $token->getLine());
    }

    public function getTag(): string
    {
        return 'deprecated';
    }
}
