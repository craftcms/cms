<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\RequireAdminNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class RequireAdminTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class RequireAdminTokenParser extends AbstractTokenParser
{
    /**
     * @inheritdoc
     */
    public function parse(Token $token): RequireAdminNode
    {
        $lineno = $token->getLine();
        $parser = $this->parser;
        $stream = $parser->getStream();

        $stream->expect(Token::BLOCK_END_TYPE);

        return new RequireAdminNode([], [], $lineno, $this->getTag());
    }

    /**
     * @inheritdoc
     */
    public function getTag(): string
    {
        return 'requireAdmin';
    }
}
