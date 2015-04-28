<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\tokenparsers;

use craft\app\web\twig\nodes\RegisterResourceNode;

/**
 * Class RegisterResourceTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RegisterResourceTokenParser extends \Twig_TokenParser
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	private $_tag;

	/**
	 * @var string
	 */
	private $_newTag;

	// Public Methods
	// =========================================================================

	/**
	 * @param string $tag
	 * @param boolean $newTag
	 * @todo Remove the $newTag stuff in Craft 4
	 */
	public function __construct($tag, $newTag = null)
	{
		$this->_tag = $tag;
		$this->_newTag = $newTag;
	}

	/**
	 * @inheritdoc
	 */
	public function parse(\Twig_Token $token)
	{
		// Is this the deprecated version?
		if ($this->_newTag !== null)
		{
			\Craft::$app->getDeprecator()->log($this->_tag, "{% $this->_tag %} is now deprecated. Use {% $this->_newTag %} instead.");
		}

		$lineno = $token->getLine();
		$stream = $this->parser->getStream();
		$expressionParser = $this->parser->getExpressionParser();

		$nodes['path'] = $expressionParser->parseExpression();

		if ($this->_newTag !== null)
		{
			$first = $stream->test(\Twig_Token::NAME_TYPE, 'first');

			if ($first)
			{
				$stream->next();
			}
		}
		else
		{
			$first = null;

			if (!$stream->test(\Twig_Token::BLOCK_END_TYPE))
			{
				$nodes['options'] = $expressionParser->parseExpression();

				if (!$stream->test(\Twig_Token::BLOCK_END_TYPE))
				{
					$nodes['key'] = $expressionParser->parseExpression();
				}
			}
		}

		$stream->expect(\Twig_Token::BLOCK_END_TYPE);

		$attributes = [
			'function' => ($this->_newTag ?: $this->_tag),
			'first'    => $first,
		];

		return new RegisterResourceNode($nodes, $attributes, $lineno, $this->getTag());
	}

	/**
	 * @inheritdoc
	 */
	public function getTag()
	{
		return $this->_tag;
	}
}
