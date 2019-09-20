<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\NamespaceNode;
use Twig\Parser;
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
        /** @var Parser $parser */
        $parser = $this->parser;
        $stream = $parser->getStream();

        $nodes = [
            'namespace' => $parser->getExpressionParser()->parseExpression(),
        ];
        $stream->expect(Token::BLOCK_END_TYPE);
        $nodes['body'] = $parser->subparse([$this, 'decideNamespaceEnd'], true);
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
