<?php
namespace Craft;

/**
 * Cache twig node.
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
			->write("\$cacheService = \Craft\craft()->templateCache;\n");

		if ($ignoreConditions)
		{
			$compiler
				->write("\$ignoreCache{$n} = (bool) ")
				->subcompile($ignoreConditions)
				->raw(";\n")
				->write("if (!\$ignoreCache{$n}) {\n")
				->indent();
		}

		$compiler->write("\$cacheBody{$n} = \$cacheService->getTemplateCache('{$key}', {$global});\n");

		if ($ignoreConditions)
		{
			$compiler
				->outdent()
				->write("}\n");
		}

		$compiler
			->write("if (empty(\$cacheBody{$n})) {\n")
			->indent();

		if ($ignoreConditions)
		{
			$compiler
				->write("if (!\$ignoreCache{$n}) {\n")
				->indent();
		}

		$compiler->write("\$cacheService->startTemplateCache('{$key}');\n");

		if ($ignoreConditions)
		{
			$compiler
				->outdent()
				->write("}\n");
		}

		$compiler
			->write("ob_start();\n")
			->subcompile($this->getNode('body'))
			->write("\$cacheBody{$n} = ob_get_clean();\n");

		if ($ignoreConditions)
		{
			$compiler
				->write("if (!\$ignoreCache{$n}) {\n")
				->indent();
		}

		$compiler->write("\$cacheService->endTemplateCache('{$key}', {$global}, ");

		if ($durationNum)
		{
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

		$compiler->raw(", \$cacheBody{$n});\n");

		if ($ignoreConditions)
		{
			$compiler
				->outdent()
				->write("}\n");
		}

		$compiler
			->outdent()
			->write("}\n")
			->write("echo \$cacheBody{$n};\n");
	}
}
