<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use Craft;
use craft\helpers\Html;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class NamespaceNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
#[YieldReady]
class NamespaceNode extends Node
{
    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        $body = new CaptureNode($this->getNode('body'), $this->getTemplateLine());

        $compiler
            ->addDebugInfo($this)
            ->write('$_namespace = ')
            ->subcompile($this->getNode('namespace'))
            ->raw(";\n")
            ->write("if (\$_namespace !== null && \$_namespace !== '') {\n")
            ->indent()
            ->write('$_originalNamespace = ' . Craft::class . "::\$app->getView()->getNamespace();\n")
            ->write(Craft::class . '::$app->getView()->setNamespace(' . Craft::class . "::\$app->getView()->namespaceInputName(\$_namespace));\n")
            ->write('$_namespacedHtml = ')
            ->subcompile($body)
            ->raw("\n")
            ->write('yield ' . Html::class . '::namespaceHtml($_namespacedHtml, $_namespace, ')
            ->raw($this->hasAttribute('withClasses') ? 'true' : 'false')
            ->raw(");\n")
            ->write(Craft::class . "::\$app->getView()->setNamespace(\$_originalNamespace);\n")
            ->outdent()
            ->write("} else {\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->outdent()
            ->write("}\n")
            ->write("unset(\$_originalNamespace, \$_namespace, \$_namespacedHtml);\n");
    }
}
