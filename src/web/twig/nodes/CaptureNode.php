<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Extension\CoreExtension;
use Twig\Node\Node;

/**
 * Captures a node's output as a raw string, like `Twig\Node\CaptureNode`.
 *
 * Unlike Twig's node, macros imported in the captured body stay available after it.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.4
 * @internal
 */
#[YieldReady]
class CaptureNode extends Node
{
    public function __construct(Node $body, int $lineno)
    {
        parent::__construct(['body' => $body], [], $lineno);
    }

    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        $useYield = $compiler->getEnvironment()->useYield();

        $compiler
            ->raw($useYield ? "implode('', iterator_to_array(" : '\\' . CoreExtension::class . '::captureOutput(')
            ->raw("(function () use (&\$context, &\$macros, \$blocks) {\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->write("return; yield;\n")
            ->outdent()
            ->write('})()')
            ->raw($useYield ? ', false));' : ');');
    }
}
