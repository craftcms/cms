<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\ExitNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class ExitTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ExitTokenParser extends AbstractTokenParser
{
    /**
     * @inheritdoc
     */
    public function parse(Token $token): ExitNode
    {
        $lineno = $token->getLine();
        $parser = $this->parser;
        $stream = $parser->getStream();

        $nodes = [];

        if ($stream->test(Token::NUMBER_TYPE)) {
            $nodes['status'] = $parser->getExpressionParser()->parseExpression();

            if (!$stream->test(Token::BLOCK_END_TYPE)) {
                $nodes['message'] = $parser->getExpressionParser()->parseExpression();
            }
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new ExitNode($nodes, [], $lineno, $this->getTag());
    }

    /**
     * @inheritdoc
     */
    public function getTag(): string
    {
        return 'exit';
    }
}
