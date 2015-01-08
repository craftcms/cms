<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\templating\twigextensions;

/**
 * Class Hook_TokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Hook_TokenParser extends \Twig_TokenParser
{
	// Public Methods
	// =========================================================================

	/**
	 * Defines the tag name.
	 *
	 * @return string
	 */
	public function getTag()
	{
		return 'hook';
	}

	/**
	 * Parses {% hook %} tags.
	 *
	 * @param \Twig_Token $token
	 *
	 * @return Hook_Node
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$hook = $this->parser->getExpressionParser()->parseExpression();
		$this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

		return new Hook_Node(['hook' => $hook], [], $lineno, $this->getTag());
	}
}
