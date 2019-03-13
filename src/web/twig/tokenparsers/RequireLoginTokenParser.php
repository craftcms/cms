<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\RequireLoginNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class RequireLoginTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RequireLoginTokenParser extends AbstractTokenParser
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new RequireLoginNode([], [], $lineno, $this->getTag());
    }

    /**
     * @inheritdoc
     */
    public function getTag()
    {
        return 'requireLogin';
    }
}
