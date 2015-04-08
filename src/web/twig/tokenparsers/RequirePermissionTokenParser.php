<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\tokenparsers;

use craft\app\web\twig\nodes\RequirePermissionNode;

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
		$permissionName = $this->parser->getExpressionParser()->parseExpression();
		$this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

		return new RequirePermissionNode(['permissionName' => $permissionName], [], $lineno, $this->getTag());
	}

	/**
	 * @inheritdoc
	 */
	public function getTag()
	{
		return 'requirePermission';
	}
}
