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
 * Class SetFallbackNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
 */
class SetFallbackNode extends SetNode
{
    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        parent::compile($compiler);

        $compiler
            ->write("foreach (\$_fallbacks as \$_fallbackName => \$_fallbackValue) {\n")
            ->indent()
            ->write(sprintf("%s::setFallback(\$_fallbackName, \$_fallbackValue);\n", Template::class))
            ->outdent()
            ->write("}\n")
            ->write("unset(\$_fallbacks, \$_fallbackName, \$_fallbackValue);\n");
    }
}
