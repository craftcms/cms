<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use CraftCms\Cms\Support\Facades\InputNamespace;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\CaptureNode;
use Twig\Node\Node;

#[YieldReady]
class NamespaceNode extends Node
{
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $capture = new CaptureNode($this->getNode('body'), $this->getTemplateLine());
        $capture->setAttribute('raw', true);

        $compiler
            ->addDebugInfo($this)
            ->write('$_namespace = ')
            ->subcompile($this->getNode('namespace'))
            ->raw(";\n")
            ->write("if (\$_namespace !== null && \$_namespace !== '') {\n")
            ->indent()
            ->write('yield '.InputNamespace::class."::namespaceInputs(function () use (&\$context, \$macros, \$blocks) {\n")
            ->indent()
            ->write('return ')
            ->subcompile($capture)
            ->raw("\n")
            ->outdent()
            ->write('}, $_namespace, withClasses: ')
            ->raw($this->hasAttribute('withClasses') ? 'true' : 'false')
            ->raw(");\n")
            ->outdent()
            ->write("} else {\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->outdent()
            ->write("}\n")
            ->write("unset(\$_namespace);\n");
    }
}
