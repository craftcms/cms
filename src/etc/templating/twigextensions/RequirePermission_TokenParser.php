<?php
namespace Craft;

/**
 * Class RequirePermission_TokenParser
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     1.0
 */
class RequirePermission_TokenParser extends \Twig_TokenParser
{
	// Public Methods
	// =========================================================================

	/**
	 * Parses {% requirePermission %} tags.
	 *
	 * @param \Twig_Token $token
	 *
	 * @return RequirePermission_Node
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$permissionName = $this->parser->getExpressionParser()->parseExpression();
		$this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

		return new RequirePermission_Node(array('permissionName' => $permissionName), array(), $lineno, $this->getTag());
	}

	/**
	 * Defines the tag name.
	 *
	 * @return string
	 */
	public function getTag()
	{
		return 'requirePermission';
	}
}
