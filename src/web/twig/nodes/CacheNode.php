<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\nodes;
use craft\app\helpers\StringHelper;

/**
 * Cache twig node.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CacheNode extends \Twig_Node
{
	// Properties
	// =========================================================================

	/**
	 * @var int
	 */
	private static $_cacheCount = 1;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$n = static::$_cacheCount++;

		$conditions = $this->getNode('conditions');
		$ignoreConditions = $this->getNode('ignoreConditions');
		$key = $this->getNode('key');
		$durationNum = $this->getAttribute('durationNum');
		$durationUnit = $this->getAttribute('durationUnit');
		$expiration = $this->getNode('expiration');
		$global = $this->getAttribute('global') ? 'true' : 'false';

		$compiler
			->addDebugInfo($this)
			->write("\$cacheService = \\Craft::\$app->templateCache;\n")
			->write("\$ignoreCache{$n} = (\\Craft::\$app->getRequest()->getIsLivePreview() || \craft\app\Craft::$app->getRequest()->getToken()");

		if ($conditions)
		{
			$compiler
				->raw(' || !(')
				->subcompile($conditions)
				->raw(')');
		}
		else if ($ignoreConditions)
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
				->write("\$cacheKey{$n} = ");

		if ($key)
		{
			$compiler->subcompile($key);
		}
		else
		{
			$compiler->raw('"'.StringHelper::randomString().'"');
		}

		$compiler
				->raw(";\n")
				->write("\$cacheBody{$n} = \$cacheService->getTemplateCache(\$cacheKey{$n}, {$global});\n")
			->outdent()
			->write("} else {\n")
			->indent()
			->write("\$cacheBody{$n} = null;\n")
			->outdent()
			->write("}\n")
			->write("if (empty(\$cacheBody{$n})) {\n")
			->indent()
				->write("if (!\$ignoreCache{$n}) {\n")
				->indent()
					->write("\$cacheService->startTemplateCache(\$cacheKey{$n});\n")
				->outdent()
				->write("}\n")
				->write("ob_start();\n")
				->subcompile($this->getNode('body'))
				->write("\$cacheBody{$n} = ob_get_clean();\n")
				->write("if (!\$ignoreCache{$n}) {\n")
				->indent()
					->write("\$cacheService->endTemplateCache(\$cacheKey{$n}, {$global}, ");

		if ($durationNum)
		{
			// So silly that PHP doesn't support "+1 week" http://www.php.net/manual/en/datetime.formats.relative.php

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
