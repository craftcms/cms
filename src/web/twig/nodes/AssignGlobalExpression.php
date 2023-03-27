<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use Twig\Compiler;
use Twig\Node\Expression\NameExpression;

/**
 * Class AssignGlobalExpression
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
 */
class AssignGlobalExpression extends NameExpression
{
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->raw('$_tmpGlobals[')
            ->string($this->getAttribute('name'))
            ->raw(']')
        ;
    }
}
