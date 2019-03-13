<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\NamespaceNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class NamespaceTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class NamespaceTokenParser extends AbstractTokenParser
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTag()
    {
        return 'namespace';
    }

    /**
     * @inheritdoc
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $nodes = [
            'namespace' => $this->parser->getExpressionParser()->parseExpression(),
        ];
        $stream->expect(Token::BLOCK_END_TYPE);
        $nodes['body'] = $this->parser->subparse([$this, 'decideNamespaceEnd'], true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new NamespaceNode($nodes, [], $lineno, $this->getTag());
    }


    /**
     * @param Token $token
     * @return bool
     */
    public function decideNamespaceEnd(Token $token): bool
    {
        return $token->test('endnamespace');
    }
}
