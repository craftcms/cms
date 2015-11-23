<?php
namespace Craft;

/**
 * Class DeprecatedTag_TokenParser
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     1.0
 */
class DeprecatedTag_TokenParser extends \Twig_TokenParser
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	private $_tag;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param string $tag
	 *
	 * @return DeprecatedTag_TokenParser
	 */
	public function __construct($tag)
	{
		$this->_tag = $tag;
	}

	/**
	 * Parses resource include tags.
	 *
	 * @param \Twig_Token $token
	 *
	 * @return DeprecatedTag_Node
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();

		// Parse until we reach the end of this tag
		while (!$stream->test(\Twig_Token::BLOCK_END_TYPE))
		{
			$stream->next();
		}

		$stream->expect(\Twig_Token::BLOCK_END_TYPE);

		$filename = $stream->getFilename();
		craft()->deprecator->log("{% {$this->_tag} %}", "The {% {$this->_tag} %} tag is no longer necessary. You can remove it from your ‘{$filename}’ template on line {$lineno}.");

		return new \Twig_Node(array(), array(), $lineno, $this->_tag);
	}

	/**
	 * Defines the tag name.
	 *
	 * @return string
	 */
	public function getTag()
	{
		return $this->_tag;
	}
}
