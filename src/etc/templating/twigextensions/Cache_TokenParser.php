<?php
namespace Craft;

/**
 * Class Cache_TokenParser
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     2.0
 */
class Cache_TokenParser extends \Twig_TokenParser
{
	// Public Methods
	// =========================================================================

	/**
	 * @return string
	 */
	public function getTag()
	{
		return 'cache';
	}

	/**
	 * Parses {% cache %}...{% endcache %} tags.
	 *
	 * @param \Twig_Token $token
	 *
	 * @return Cache_Node
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();

		$nodes = array(
			'expiration' => null,
			'conditions' => null,
			'ignoreConditions' => null,
			'key' => null,
			'body' => null,
		);

		$attributes = array(
			'global' => false,
			'durationNum' => null,
			'durationUnit' => null,
		);

		if ($stream->test(\Twig_Token::NAME_TYPE, 'globally'))
		{
			$attributes['global'] = true;
			$stream->next();
		}

		if ($stream->test(\Twig_Token::NAME_TYPE, 'using'))
		{
			$stream->next();
			$stream->expect(\Twig_Token::NAME_TYPE, 'key');
			$nodes['key'] = $this->parser->getExpressionParser()->parseExpression();
		}

		if ($stream->test(\Twig_Token::NAME_TYPE, 'for'))
		{
			$stream->next();
			$attributes['durationNum'] = $stream->expect(\Twig_Token::NUMBER_TYPE)->getValue();
			$attributes['durationUnit'] = $stream->expect(\Twig_Token::NAME_TYPE, array('sec','secs','second','seconds','min','mins','minute','minutes','hour','hours','day','days','fortnight','fortnights','forthnight','forthnights','month','months','year','years','week','weeks'))->getValue();
		}
		else if ($stream->test(\Twig_Token::NAME_TYPE, 'until'))
		{
			$stream->next();
			$nodes['expiration'] = $this->parser->getExpressionParser()->parseExpression();
		}

		if ($stream->test(\Twig_Token::NAME_TYPE, 'if'))
		{
		    $stream->next();
		    $nodes['conditions'] = $this->parser->getExpressionParser()->parseExpression();
		}
		else if ($stream->test(\Twig_Token::NAME_TYPE, 'unless'))
		{
		    $stream->next();
		    $nodes['ignoreConditions'] = $this->parser->getExpressionParser()->parseExpression();
		}

		$stream->expect(\Twig_Token::BLOCK_END_TYPE);
		$nodes['body'] = $this->parser->subparse(array($this, 'decideCacheEnd'), true);
		$stream->expect(\Twig_Token::BLOCK_END_TYPE);

		return new Cache_Node($nodes, $attributes, $lineno, $this->getTag());
	}

	/**
	 * @param \Twig_Token $token
	 *
	 * @return bool
	 */
	public function decideCacheEnd(\Twig_Token $token)
	{
		return $token->test('endcache');
	}
}
