<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\templating\twigextensions;

/**
 * Class RequireAdmin_TokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RequireAdmin_TokenParser extends \Twig_TokenParser
{
	// Public Methods
	// =========================================================================

	/**
	 * Parses {% requireAdmin %} tags.
	 *
	 * @param \Twig_Token $token
	 *
	 * @return RequireAdmin_Node
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

		return new RequireAdmin_Node([], [], $lineno, $this->getTag());
	}

	/**
	 * Defines the tag name.
	 *
	 * @return string
	 */
	public function getTag()
	{
		return 'requireAdmin';
	}
}
