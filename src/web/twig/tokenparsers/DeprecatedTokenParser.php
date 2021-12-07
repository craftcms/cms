<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\DeprecatedNode;
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
 * @since 3.7.24
 */
class DeprecatedTokenParser extends AbstractTokenParser
{
    /**
     * @inheritdoc
     */
    public function parse(Token $token): DeprecatedNode
    {
        $expr = $this->parser->getExpressionParser()->parseExpression();
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);
        return new DeprecatedNode($expr, $token->getLine(), $this->getTag());
    }

    /**
     * @inheritdoc
     */
    public function getTag(): string
    {
        return 'deprecated';
    }
}
