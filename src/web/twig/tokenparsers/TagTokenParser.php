<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\TagNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class TagTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class TagTokenParser extends AbstractTokenParser
{
    /**
     * @inheritdoc
     */
    public function getTag(): string
    {
        return 'tag';
    }

    /**
     * @inheritdoc
     */
    public function parse(Token $token): TagNode
    {
        $lineno = $token->getLine();
        $expressionParser = $this->parser->getExpressionParser();
        $stream = $this->parser->getStream();

        $nodes = [
            'name' => $expressionParser->parseExpression(),
        ];

        if ($stream->test(Token::NAME_TYPE, 'with')) {
            $stream->next();
            $nodes['options'] = $expressionParser->parseExpression();
        }

        $stream->expect(Token::BLOCK_END_TYPE);
        $nodes['content'] = $this->parser->subparse(function(Token $token) {
            return $token->test('endtag');
        }, true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new TagNode($nodes, [], $lineno, $this->getTag());
    }
}
