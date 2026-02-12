<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use Craft;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class HeaderNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class HeaderNode extends Node
{
    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->write('$_headerParts = array_map(\'trim\', explode(\':\', ')
            ->subcompile($this->getNode('header'))
            ->raw(", 2));\n")
            ->write(Craft::class . "::\$app->getResponse()->getHeaders()->set(\$_headerParts[0], \$_headerParts[1] ?? '');\n")
            ->write("unset(\$_headerParts);\n");
    }
}
