<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\templating\twigextensions;

/**
 * Class Namespace_Node
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Namespace_Node extends \Twig_Node
{
	// Public Methods
	// =========================================================================

	/**
	 * Compiles a Namespace_Node into PHP.
	 *
	 * @param \Twig_Compiler $compiler
	 *
	 * @return null
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
		    ->addDebugInfo($this)
		    ->write('$_namespace = ')
		    ->subcompile($this->getNode('namespace'))
		    ->raw(";\n")
		    ->write("if (\$_namespace) {\n")
		    ->indent()
		        ->write("\$_originalNamespace = \Craft\Craft::$app->templates->getNamespace();\n")
		        ->write("\Craft\Craft::$app->templates->setNamespace(\Craft\Craft::$app->templates->namespaceInputName(\$_namespace));\n")
		        ->write("ob_start();\n")
		        ->write("try {\n")
		        ->indent()
		            ->subcompile($this->getNode('body'))
		        ->outdent()
		        ->write("} catch (Exception \$e) {\n")
		        ->indent()
		            ->write("ob_end_clean();\n\n")
		            ->write("throw \$e;\n")
		        ->outdent()
		        ->write("}\n")
		        ->write("echo \Craft\Craft::$app->templates->namespaceInputs(ob_get_clean(), \$_namespace);\n")
		        ->write("\Craft\Craft::$app->templates->setNamespace(\$_originalNamespace);\n")
		    ->outdent()
		    ->write("} else {\n")
		    ->indent()
		        ->subcompile($this->getNode('body'))
		    ->outdent()
		    ->write("}\n")
		    ->write("unset(\$_originalNamespace, \$_namespace);\n");
	}
}
