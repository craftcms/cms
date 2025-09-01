<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\DumpNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class DumpTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class DumpTokenParser extends AbstractTokenParser
{
    /**
     * @inheritdoc
     */
    public function parse(Token $token): DumpNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $nodes = [];

        if (!$stream->test(Token::BLOCK_END_TYPE)) {
            $nodes['var'] = $this->parser->parseExpression();
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new DumpNode($nodes, [], $lineno);
    }

    /**
     * @inheritdoc
     */
    public function getTag(): string
    {
        return 'dump';
    }
}
