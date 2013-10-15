<?php
namespace Craft;

/**
 *
 */
class Namespace_Node extends \Twig_Node
{
	/**
	 * Compiles a Namespace_Node into PHP.
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
			->addDebugInfo($this)
			->write("\$_oldNamespace = \Craft\craft()->templates->getNamespace();\n")
			->write('\Craft\craft()->templates->setNamespace(')
			->subcompile($this->getNode('namespace'))
			->raw(");\n")
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
            ->write('echo \Craft\craft()->templates->namespaceInputs(ob_get_clean(), ')
            ->subcompile($this->getNode('namespace'))
            ->raw(");\n")
			->write("\Craft\craft()->templates->setNamespace(\$_oldNamespace);\n")
			->write("unset(\$_oldNamespace);\n");
	}
}
