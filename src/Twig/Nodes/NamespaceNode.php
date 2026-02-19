<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use Craft;
use CraftCms\Cms\Support\Html;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
final class NamespaceNode extends Node
{
    /**
     * {@inheritdoc}
     */
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write('$_namespace = ')
            ->subcompile($this->getNode('namespace'))
            ->raw(";\n")
            ->write("if (\$_namespace !== null && \$_namespace !== '') {\n")
            ->indent()
            ->write('$_originalNamespace = '.Craft::class."::\$app->getView()->getNamespace();\n")
            ->write(Craft::class.'::$app->getView()->setNamespace('.Craft::class."::\$app->getView()->namespaceInputName(\$_namespace));\n")
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
            ->write('yield '.Html::class.'::namespaceHtml(ob_get_clean(), $_namespace, ')
            ->raw($this->hasAttribute('withClasses') ? 'true' : 'false')
            ->raw(");\n")
            ->write(Craft::class."::\$app->getView()->setNamespace(\$_originalNamespace);\n")
            ->outdent()
            ->write("} else {\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->outdent()
            ->write("}\n")
            ->write("unset(\$_originalNamespace, \$_namespace);\n");
    }
}
