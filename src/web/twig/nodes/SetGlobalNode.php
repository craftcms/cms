<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use craft\helpers\Template;
use Twig\Compiler;
use Twig\Node\SetNode;

/**
 * Class SetGlobalNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
 */
class SetGlobalNode extends SetNode
{
    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        parent::compile($compiler);

        $compiler
            ->write("foreach (\$_tmpGlobals as \$_tmpGlobalName => \$_tmpGlobalValue) {\n")
            ->indent()
            ->write(sprintf("%s::setFallback(\$_tmpGlobalName, \$_tmpGlobalValue);\n", Template::class))
            ->outdent()
            ->write("}\n")
            ->write("unset(\$_tmpGlobals, \$_tmpGlobalName, \$_tmpGlobalValue);\n");
    }
}
