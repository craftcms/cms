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
 * @since 3.0.0
 */
class NamespaceTokenParser extends AbstractTokenParser
{
    /**
     * @inheritdoc
     */
    public function getTag(): string
    {
        return 'namespace';
    }

    /**
     * @inheritdoc
     */
    public function parse(Token $token): NamespaceNode
    {
        $lineno = $token->getLine();
        $parser = $this->parser;
        $stream = $parser->getStream();

        $nodes = [
            'namespace' => $parser->getExpressionParser()->parseExpression(),
        ];
        $attributes = [];
        if ($stream->test('withClasses')) {
            $attributes['withClasses'] = true;
            $stream->next();
        }
        $stream->expect(Token::BLOCK_END_TYPE);
        $nodes['body'] = $parser->subparse([$this, 'decideNamespaceEnd'], true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new NamespaceNode($nodes, $attributes, $lineno, $this->getTag());
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
