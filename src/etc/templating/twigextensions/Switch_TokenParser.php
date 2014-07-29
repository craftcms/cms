<?php
namespace Craft;

/**
 * Class Switch_TokenParser that parses {% switch %} tags.
 *
 * Based on the rejected Twig pull request: https://github.com/fabpot/Twig/pull/185
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     1.0
 */
class Switch_TokenParser extends \Twig_TokenParser
{
	/**
	 * Gets the tag name associated with this token parser.
	 *
	 * @param string The tag name
	 *
	 * @return string
	 */
	public function getTag()
	{
		return 'switch';
	}

	/**
	 * Parses a token and returns a node.
	 *
	 * @param \Twig_Token $token
	 *
	 * @throws \Twig_Error_Syntax
	 * @return Switch_Node
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();

		$name = $this->parser->getExpressionParser()->parseExpression();
		$stream->expect(\Twig_Token::BLOCK_END_TYPE);

		// There can be some whitespace between the {% switch %} and first {% case %} tag.
		while ($stream->getCurrent()->getType() == \Twig_Token::TEXT_TYPE && trim($stream->getCurrent()->getValue()) == '')
		{
			$stream->next();
		}

		$stream->expect(\Twig_Token::BLOCK_START_TYPE);

		$cases = array();
		$default = null;
		$end = false;

		while (!$end)
		{
			$next = $stream->next();

			switch ($next->getValue())
			{
				case 'case':
				{
					$expr = $this->parser->getExpressionParser()->parseExpression();
					$stream->expect(\Twig_Token::BLOCK_END_TYPE);
					$body = $this->parser->subparse(array($this, 'decideIfFork'));
					$cases[] = array(
						'expr' => $expr,
						'body' => $body
					);
					break;
				}
				case 'default':
				{
					$stream->expect(\Twig_Token::BLOCK_END_TYPE);
					$default = $this->parser->subparse(array($this, 'decideIfEnd'));
					break;
				}
				case 'endswitch':
				{
					$end = true;
					break;
				}
				default:
				{
					throw new \Twig_Error_Syntax(sprintf('Unexpected end of template. Twig was looking for the following tags "case", "default", or "endswitch" to close the "switch" block started at line %d)', $lineno), -1);
				}
			}
		}

		$stream->expect(\Twig_Token::BLOCK_END_TYPE);

		return new Switch_Node($name, new \Twig_Node($cases), $default, $lineno, $this->getTag());
	}

	public function decideIfFork($token)
	{
		return $token->test(array('case', 'default', 'endswitch'));
	}

	public function decideIfEnd($token)
	{
		return $token->test(array('endswitch'));
	}
}
