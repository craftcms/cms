<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\Twig\Nodes\expressions\binaries;

use CraftCms\Cms\Twig\Extensions\CoreTwigExtension;
use Twig\Compiler;
use Twig\Node\Expression\Binary\AbstractBinary;

/**
 * Class HasSomeBinary
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.4.3
 */
class HasSomeBinary extends AbstractBinary
{
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->raw(sprintf('%s::arraySome($this->env, ', CoreTwigExtension::class))
            ->subcompile($this->getNode('left'))
            ->raw(', ')
            ->subcompile($this->getNode('right'))
            ->raw(')')
        ;
    }

    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('');
    }
}
