<?php
namespace Craft;

/**
 * Class Header_TokenParser
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     1.3
 */
class Header_TokenParser extends \Twig_TokenParser
{
	// Public Methods
	// =========================================================================

	/**
	 * Parses {% requireLogin %} tags.
	 *
	 * @param \Twig_Token $token
	 *
	 * @return RequireLogin_Node
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$header = $this->parser->getExpressionParser()->parseExpression();
		$this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

		return new Header_Node(array('header' => $header), array(), $lineno, $this->getTag());
	}

	/**
	 * Defines the tag name.
	 *
	 * @return string
	 */
	public function getTag()
	{
		return 'header';
	}
}
