<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\RequireEditionNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class RequireEditionTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RequireEditionTokenParser extends AbstractTokenParser
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
            'editionName' => $this->parser->getExpressionParser()->parseExpression(),
        ];
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new RequireEditionNode($nodes, [], $lineno, $this->getTag());
    }

    /**
     * @inheritdoc
     */
    public function getTag()
    {
        return 'requireEdition';
    }
}
