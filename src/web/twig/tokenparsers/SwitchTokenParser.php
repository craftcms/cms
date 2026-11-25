<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\BaseNode;
use craft\web\twig\nodes\SwitchNode;
use Twig\Error\SyntaxError;
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
        $stream = $this->parser->getStream();

        $nodes = [
            'value' => $this->parser->parseExpression(),
        ];

        $stream->expect(Token::BLOCK_END_TYPE);

        // There can be some whitespace between the {% switch %} and first {% case %} tag.
        while ($stream->getCurrent()->test(Token::TEXT_TYPE) && trim($stream->getCurrent()->getValue()) === '') {
            $stream->next();
        }

        $stream->expect(Token::BLOCK_START_TYPE);

        $cases = [];
        $end = false;

        while (!$end) {
            $next = $stream->next();

            switch ($next->getValue()) {
                case 'case':
                    $values = [];
                    while (true) {
                        $values[] = $this->parser->parseExpression();
                        // Multiple allowed values?
                        if ($stream->test(Token::OPERATOR_TYPE, 'or')) {
                            $stream->next();
                        } else {
                            break;
                        }
                    }
                    $stream->expect(Token::BLOCK_END_TYPE);
                    $body = $this->parser->subparse([$this, 'decideIfFork']);
                    $cases[] = new BaseNode([
                        'values' => new BaseNode($values),
                        'body' => $body,
                    ]);
                    break;
                case 'default':
                    $stream->expect(Token::BLOCK_END_TYPE);
                    $nodes['default'] = $this->parser->subparse([$this, 'decideIfEnd']);
                    break;
                case 'endswitch':
                    $end = true;
                    break;
                default:
                    throw new SyntaxError(sprintf('Unexpected end of template. Twig was looking for the following tags "case", "default", or "endswitch" to close the "switch" block started at line %d)', $lineno), -1);
            }
        }

        $nodes['cases'] = new BaseNode($cases);

        $stream->expect(Token::BLOCK_END_TYPE);

        return new SwitchNode($nodes, [], $lineno);
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
