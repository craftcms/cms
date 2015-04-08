<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\nodes;

/**
 * Class NamespaceNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class NamespaceNode extends \Twig_Node
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
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
		        ->write("\$_originalNamespace = \\Craft::\$app->getView()->getNamespace();\n")
		        ->write("\\Craft::\$app->getView()->setNamespace(\\Craft::\$app->getView()->namespaceInputName(\$_namespace));\n")
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
		        ->write("echo \\Craft::\$app->getView()->namespaceInputs(ob_get_clean(), \$_namespace);\n")
		        ->write("\\Craft::\$app->getView()->setNamespace(\$_originalNamespace);\n")
		    ->outdent()
		    ->write("} else {\n")
		    ->indent()
		        ->subcompile($this->getNode('body'))
		    ->outdent()
		    ->write("}\n")
		    ->write("unset(\$_originalNamespace, \$_namespace);\n");
	}
}
