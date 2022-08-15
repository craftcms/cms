<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\SwitchNode;
use Twig\Error\SyntaxError;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class SwitchTokenParser that parses {% switch %} tags.
 * Based on the rejected Twig pull request: https://github.com/fabpot/Twig/pull/185
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SwitchTokenParser extends AbstractTokenParser
{
    /**
     * @inheritdoc
     */
    public function getTag(): string
    {
        return 'switch';
    }

    /**
     * @inheritdoc
     */
    public function parse(Token $token): SwitchNode
    {
        $lineno = $token->getLine();
        $parser = $this->parser;
        $stream = $parser->getStream();

        $nodes = [
            'value' => $parser->getExpressionParser()->parseExpression(),
        ];

        $stream->expect(Token::BLOCK_END_TYPE);

        // There can be some whitespace between the {% switch %} and first {% case %} tag.
        while ($stream->getCurrent()->getType() == Token::TEXT_TYPE && trim($stream->getCurrent()->getValue()) === '') {
            $stream->next();
        }

        $stream->expect(Token::BLOCK_START_TYPE);

        $expressionParser = $parser->getExpressionParser();
        $cases = [];
        $end = false;

        while (!$end) {
            $next = $stream->next();

            switch ($next->getValue()) {
                case 'case':
                    $values = [];
                    while (true) {
                        $values[] = $expressionParser->parsePrimaryExpression();
                        // Multiple allowed values?
                        if ($stream->test(Token::OPERATOR_TYPE, 'or')) {
                            $stream->next();
                        } else {
                            break;
                        }
                    }
                    $stream->expect(Token::BLOCK_END_TYPE);
                    $body = $parser->subparse([$this, 'decideIfFork']);
                    $cases[] = new Node([
                        'values' => new Node($values),
                        'body' => $body,
                    ]);
                    break;
                case 'default':
                    $stream->expect(Token::BLOCK_END_TYPE);
                    $nodes['default'] = $parser->subparse([$this, 'decideIfEnd']);
                    break;
                case 'endswitch':
                    $end = true;
                    break;
                default:
                    throw new SyntaxError(sprintf('Unexpected end of template. Twig was looking for the following tags "case", "default", or "endswitch" to close the "switch" block started at line %d)', $lineno), -1);
            }
        }

        $nodes['cases'] = new Node($cases);

        $stream->expect(Token::BLOCK_END_TYPE);

        return new SwitchNode($nodes, [], $lineno, $this->getTag());
    }

    /**
     * @param Token $token
     * @return bool
     */
    public function decideIfFork(Token $token): bool
    {
        return $token->test(['case', 'default', 'endswitch']);
    }

    /**
     * @param Token $token
     * @return bool
     */
    public function decideIfEnd(Token $token): bool
    {
        return $token->test(['endswitch']);
    }
}
