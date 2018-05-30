<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\HookNode;

/**
 * Class HookTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class HookTokenParser extends \Twig_TokenParser
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTag()
    {
        return 'hook';
    }

    /**
     * @inheritdoc
     */
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();
        $nodes = [
            'hook' => $this->parser->getExpressionParser()->parseExpression(),
        ];
        $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

        return new HookNode($nodes, [], $lineno, $this->getTag());
    }
}
