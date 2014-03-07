<?php

/*
 * This file is part of twig-cache-extension.
 *
 * (c) Alexander <iam.asm89@gmail.com>
 * with edits by Pixel & Tonic <http://pixelandtonic.com> and Connor Smith <connor@sphinx.io>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the Software), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED AS IS, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Craft;

/**
 * Parser for cache/endcache blocks.
 */
class Cache_TokenParser extends \Twig_TokenParser
{
	/**
	 * @param \Twig_Token $token
	 * @return boolean
	 */
	public function decideCacheEnd(\Twig_Token $token)
	{
		return $token->test('endcache');
	}

	/**
	 * @return string
	 */
	public function getTag()
	{
		return 'cache';
	}

	/**
	 * @param \Twig_Token $token
	 * @return CacheNode|\Twig_NodeInterface
	 */
	public function parse(\Twig_Token $token)
	{
		$lineNo = $token->getLine();
		$stream = $this->parser->getStream();

		$key = $this->parser->getExpressionParser()->parseExpression();

		$stream->expect(\Twig_Token::BLOCK_END_TYPE);
		$body = $this->parser->subparse(array($this, 'decideCacheEnd'), true);
		$stream->expect(\Twig_Token::BLOCK_END_TYPE);

		return new Cache_Node('', $key, $body, $lineNo, $this->getTag());
	}
}
