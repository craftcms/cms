<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\RenderNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class RenderTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class RenderTokenParser extends AbstractTokenParser
{
    /**
     * @inheritdoc
     */
    public function getTag(): string
    {
        return 'render';
    }

    /**
     * @inheritdoc
     */
    public function parse(Token $token): RenderNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $objects = $this->parser->parseExpression();
        $variables = null;

        if ($stream->nextIf(Token::NAME_TYPE, 'with')) {
            $variables = $this->parser->parseExpression();
        }

        $only = (bool)$stream->nextIf(Token::NAME_TYPE, 'only');

        $stream->expect(Token::BLOCK_END_TYPE);

        return new RenderNode($objects, $variables, $only, false, $lineno);
    }
}
