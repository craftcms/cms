<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\HeaderNode;
use Twig\Parser;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class HeaderTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class HeaderTokenParser extends AbstractTokenParser
{
    /**
     * @inheritdoc
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        /** @var Parser $parser */
        $parser = $this->parser;
        $stream = $parser->getStream();

        $nodes = [
            'header' => $parser->getExpressionParser()->parseExpression(),
        ];
        $stream->expect(Token::BLOCK_END_TYPE);

        return new HeaderNode($nodes, [], $lineno, $this->getTag());
    }

    /**
     * @inheritdoc
     */
    public function getTag()
    {
        return 'header';
    }
}
