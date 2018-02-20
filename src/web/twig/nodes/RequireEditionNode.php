<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use yii\web\NotFoundHttpException;

/**
 * Class RequireEditionNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RequireEditionNode extends \Twig_Node
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
            ->write('if (\Craft::$app->getEdition() < ')
            ->subcompile($this->getNode('editionName'))
            ->raw(")\n")
            ->write("{\n")
            ->indent()
            ->write('throw new '.NotFoundHttpException::class.";\n")
            ->outdent()
            ->write("}\n");
    }
}
