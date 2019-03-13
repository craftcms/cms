<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\RedirectNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Parser;
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
        /** @var Parser $parser */
        $parser = $this->parser;
        $stream = $parser->getStream();

        $nodes = [
            'path' => $parser->getExpressionParser()->parseExpression(),
        ];

        if ($stream->test(Token::NUMBER_TYPE)) {
            $nodes['httpStatusCode'] = $parser->getExpressionParser()->parseExpression();
        } else {
            $nodes['httpStatusCode'] = new ConstantExpression(302, 1);
        }

        // Parse flash message(s)
        while ($stream->test(Token::NAME_TYPE, 'with')) {
            $stream->next();
            $type = $stream->expect(Token::NAME_TYPE, ['notice', 'error'])->getValue();
            $nodes[$type] = $parser->getExpressionParser()->parseExpression();
        }

        $stream->expect(Token::BLOCK_END_TYPE);

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
