<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\RedirectNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class RedirectTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RedirectTokenParser extends AbstractTokenParser
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $nodes = [
            'path' => $this->parser->getExpressionParser()->parseExpression(),
        ];

        if ($stream->test(Token::NUMBER_TYPE)) {
            $nodes['httpStatusCode'] = $this->parser->getExpressionParser()->parseExpression();
        } else {
            $nodes['httpStatusCode'] = new ConstantExpression(302, 1);
        }

        // Parse flash message(s)
        while ($stream->test(Token::NAME_TYPE, 'with')) {
            $stream->next();
            $type = $stream->expect(Token::NAME_TYPE, ['notice', 'error'])->getValue();
            $nodes[$type] = $this->parser->getExpressionParser()->parseExpression();
        }

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new RedirectNode($nodes, [], $lineno, $this->getTag());
    }

    /**
     * @inheritdoc
     */
    public function getTag()
    {
        return 'redirect';
    }
}
