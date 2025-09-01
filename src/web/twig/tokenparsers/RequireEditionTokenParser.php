<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\RequireEditionNode;
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
    public function parse(Token $token): RequireEditionNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $nodes = [
            'editionName' => $this->parser->parseExpression(),
        ];
        $stream->expect(Token::BLOCK_END_TYPE);

        return new RequireEditionNode($nodes, [], $lineno);
    }

    /**
     * @inheritdoc
     */
    public function getTag(): string
    {
        return 'requireEdition';
    }
}
