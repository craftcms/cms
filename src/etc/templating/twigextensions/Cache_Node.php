<?php
namespace Craft;

/**
 * Cache twig node.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     1.0
 */
class Cache_Node extends \Twig_Node
{
	private static $_cacheCount = 1;

	/**
	 * @param \Twig_Compiler $compiler
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$n = self::$_cacheCount++;
		$key = StringHelper::randomString();

		$ignoreConditions = $this->getNode('ignoreConditions');
		$durationNum = $this->getAttribute('durationNum');
		$durationUnit = $this->getAttribute('durationUnit');
		$expiration = $this->getNode('expiration');
		$global = $this->getAttribute('global') ? 'true' : 'false';

		$compiler
			->addDebugInfo($this)
			->write("\$cacheService = \Craft\craft()->templateCache;\n")
			->write("\$ignoreCache{$n} = (\Craft\craft()->request->isLivePreview() || \Craft\craft()->request->getToken()");

		if ($ignoreConditions)
		{
			$compiler
				->raw(' || (')
				->subcompile($ignoreConditions)
				->raw(')');
		}

		$compiler
			->raw(");\n")
			->write("if (!\$ignoreCache{$n}) {\n")
			->indent()
				->write("\$cacheBody{$n} = \$cacheService->getTemplateCache('{$key}', {$global});\n")
			->outdent()
			->write("}\n")
			->write("if (empty(\$cacheBody{$n})) {\n")
			->indent()
				->write("if (!\$ignoreCache{$n}) {\n")
				->indent()
					->write("\$cacheService->startTemplateCache('{$key}');\n")
				->outdent()
				->write("}\n")
				->write("ob_start();\n")
				->subcompile($this->getNode('body'))
				->write("\$cacheBody{$n} = ob_get_clean();\n")
				->write("if (!\$ignoreCache{$n}) {\n")
				->indent()
					->write("\$cacheService->endTemplateCache('{$key}', {$global}, ");

		if ($durationNum)
		{
			// So silly that PHP doesn't support "+1 week"
			// http://www.php.net/manual/en/datetime.formats.relative.php

			if ($durationUnit == 'week')
			{
				if ($durationNum == 1)
				{
					$durationNum = 7;
					$durationUnit = 'days';
				}
				else
				{
					$durationUnit = 'weeks';
				}
			}

			$compiler->raw("'+{$durationNum} {$durationUnit}'");
		}
		else
		{
			$compiler->raw('null');
		}

		$compiler->raw(', ');

		if ($expiration)
		{
			$compiler->subcompile($expiration);
		}
		else
		{
			$compiler->raw('null');
		}

		$compiler
					->raw(", \$cacheBody{$n});\n")
				->outdent()
				->write("}\n")
			->outdent()
			->write("}\n")
			->write("echo \$cacheBody{$n};\n");
	}
}
