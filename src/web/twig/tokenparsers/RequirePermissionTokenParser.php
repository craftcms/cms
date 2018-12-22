<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\RequirePermissionNode;

/**
 * Class RequirePermissionTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RequirePermissionTokenParser extends \Twig_TokenParser
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();
        $nodes = [
            'permissionName' => $this->parser->getExpressionParser()->parseExpression(),
        ];
        $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

        return new RequirePermissionNode($nodes, [], $lineno, $this->getTag());
    }

    /**
     * @inheritdoc
     */
    public function getTag()
    {
        return 'requirePermission';
    }
}
