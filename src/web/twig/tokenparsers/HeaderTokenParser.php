<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\HeaderNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class HeaderTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class HeaderTokenParser extends AbstractTokenParser
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $nodes = [
            'header' => $this->parser->getExpressionParser()->parseExpression(),
        ];
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

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
