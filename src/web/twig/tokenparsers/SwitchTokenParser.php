<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\tokenparsers;

use craft\app\web\twig\nodes\SwitchNode;

/**
 * Class SwitchTokenParser that parses {% switch %} tags.
 *
 * Based on the rejected Twig pull request: https://github.com/fabpot/Twig/pull/185
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SwitchTokenParser extends \Twig_TokenParser
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getTag()
	{
		return 'switch';
	}

	/**
	 * @inheritdoc
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

		$cases = [];
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
					$body = $this->parser->subparse([$this, 'decideIfFork']);
					$cases[] = [
						'expr' => $expr,
						'body' => $body
					];
					break;
				}
				case 'default':
				{
					$stream->expect(\Twig_Token::BLOCK_END_TYPE);
					$default = $this->parser->subparse([$this, 'decideIfEnd']);
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

		return new SwitchNode($name, new \Twig_Node($cases), $default, $lineno, $this->getTag());
	}

	/**
	 * @param $token
	 *
	 * @return mixed
	 */
	public function decideIfFork($token)
	{
		return $token->test(['case', 'default', 'endswitch']);
	}

	/**
	 * @param $token
	 *
	 * @return mixed
	 */
	public function decideIfEnd($token)
	{
		return $token->test(['endswitch']);
	}
}
