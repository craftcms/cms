<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\NamespaceNode;

/**
 * Class NamespaceTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class NamespaceTokenParser extends \Twig_TokenParser
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
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $nodes = [
            'namespace' => $this->parser->getExpressionParser()->parseExpression(),
        ];
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);
        $nodes['body'] = $this->parser->subparse([$this, 'decideNamespaceEnd'], true);
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new NamespaceNode($nodes, [], $lineno, $this->getTag());
    }


    /**
     * @param \Twig_Token $token
     * @return bool
     */
    public function decideNamespaceEnd(\Twig_Token $token): bool
    {
        return $token->test('endnamespace');
    }
}
