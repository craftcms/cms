<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\RequireEditionNode;
use Twig\Parser;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class RequireEditionTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class RequireEditionTokenParser extends AbstractTokenParser
{
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
            'editionName' => $parser->getExpressionParser()->parseExpression(),
        ];
        $stream->expect(Token::BLOCK_END_TYPE);

        return new RequireEditionNode($nodes, [], $lineno, $this->getTag());
    }

    /**
     * @inheritdoc
     */
    public function getTag()
    {
        return 'requireEdition';
    }
}
