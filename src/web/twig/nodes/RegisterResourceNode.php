<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\nodes;

/**
 * Class RegisterResourceNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RegisterResourceNode extends \Twig_Node
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$function = $this->getAttribute('function');
		$path = $this->getNode('path');

		$compiler
			->addDebugInfo($this)
			->write('\Craft::$app->getView()->'.$function.'(')
			->subcompile($path);

		if ($this->hasNode('options'))
		{
			$compiler
				->raw(', ')
				->subcompile($this->getNode('options'))
			;

			if ($this->hasNode('key'))
			{
				$compiler
					->raw(', ')
					->subcompile($this->getNode('key'))
				;
			}
		}
		else if ($this->getAttribute('first'))
		{
			$compiler->raw(', View::POS_END');
		}

		$compiler->raw(");\n");
	}
}
