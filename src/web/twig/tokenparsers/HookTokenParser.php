<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\HookNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class HookTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class HookTokenParser extends AbstractTokenParser
{
    /**
     * @inheritdoc
     */
    public function getTag(): string
    {
        return 'hook';
    }

    /**
     * @inheritdoc
     */
    public function parse(Token $token): HookNode
    {
        $lineno = $token->getLine();
        $parser = $this->parser;
        $stream = $parser->getStream();

        $nodes = [
            'hook' => $parser->getExpressionParser()->parseExpression(),
        ];
        $stream->expect(Token::BLOCK_END_TYPE);

        return new HookNode($nodes, [], $lineno, $this->getTag());
    }
}
