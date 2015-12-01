<?php
namespace Craft;

/**
 * Class IncludeResource_TokenParser
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     1.0
 */
class IncludeResource_TokenParser extends \Twig_TokenParser
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	private $_tag;

	/**
	 * @var boolean
	 */
	private $_allowTagPair;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param string $tag
	 *
	 * @return IncludeResource_TokenParser
	 */
	public function __construct($tag, $allowTagPair = false)
	{
		$this->_tag = $tag;
		$this->_allowTagPair = $allowTagPair;
	}

	/**
	 * Parses resource include tags.
	 *
	 * @param \Twig_Token $token
	 *
	 * @return IncludeResource_Node
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();

		if ($this->_allowTagPair && ($stream->test(\Twig_Token::NAME_TYPE, 'first') || $stream->test(\Twig_Token::BLOCK_END_TYPE)))
		{
			$capture = true;

			$first = $this->_getFirstToken($stream);
			$stream->expect(\Twig_Token::BLOCK_END_TYPE);
			$value = $this->parser->subparse(array($this, 'decideBlockEnd'), true);
			$stream->expect(\Twig_Token::BLOCK_END_TYPE);
		}
		else
		{
			$capture = false;

			$value = $this->parser->getExpressionParser()->parseExpression();
			$first = $this->_getFirstToken($stream);
			$stream->expect(\Twig_Token::BLOCK_END_TYPE);
		}

		$nodes = array(
			'value' => $value,
		);

		$attributes = array(
			'function' => $this->_tag,
			'capture'  => $capture,
			'first'    => $first,
		);

		return new IncludeResource_Node($nodes, $attributes, $lineno, $this->getTag());
	}

	public function decideBlockEnd(\Twig_Token $token)
	{
		return $token->test('end'.strtolower($this->_tag));
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

	// Private Methods
	// =========================================================================

	private function _getFirstToken($stream)
	{
		$first = $stream->test(\Twig_Token::NAME_TYPE, 'first');

		if ($first)
		{
			$stream->next();
		}

		return $first;
	}
}
