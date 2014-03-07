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
 * Cache twig node.
 */
class Cache_Node extends \Twig_Node
{
	private static $_cacheCount = 1;

	/**
	 * @param array                 $annotation
	 * @param \Twig_Node_Expression $keyInfo
	 * @param \Twig_NodeInterface   $body
	 * @param null|string           $lineNo
	 * @param null                  $tag
	 */
	public function __construct($annotation, \Twig_Node_Expression $keyInfo, \Twig_NodeInterface $body, $lineNo, $tag = null)
	{
		parent::__construct(array('keyInfo' => $keyInfo, 'body' => $body), array('annotation' => $annotation), $lineNo, $tag);
	}

	/**
	 * @param \Twig_Compiler $compiler
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$counter = self::$_cacheCount++;

		$compiler
			->addDebugInfo($this)
			->write("\$craftCacheStrategy".$counter." = \$this->getEnvironment()->getExtension('craft')->getCacheStrategy();\n")
			->write("\$craftKey".$counter." = \$craftCacheStrategy".$counter."->generateKey(")
				->subcompile($this->getNode('keyInfo'))
			->write(");\n")
			->write("\$craftCacheBody".$counter." = \$craftCacheStrategy".$counter."->fetchBlock(\$craftKey".$counter.");\n")
			->write("if (\$craftCacheBody".$counter." === false) {\n")
			->indent()
				->write("ob_start();\n")
					->indent()
						->subcompile($this->getNode('body'))
					->outdent()
				->write("\n")
				->write("\$craftCacheBody".$counter." = ob_get_clean();\n")
				->write("\$craftCacheStrategy".$counter."->saveBlock(\$craftKey".$counter.", \$craftCacheBody".$counter.");\n")
			->outdent()
			->write("}\n")
			->write("echo \$craftCacheBody".$counter.";\n")
		;
	}
}
