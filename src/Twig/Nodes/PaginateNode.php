<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\Twig\Nodes;

use craft\helpers\Template;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Represents a paginate node.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 */
#[YieldReady]
class PaginateNode extends Node
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write('[')
            ->subcompile($this->getNode('infoVariable'))
            ->raw(', ')
            ->subcompile($this->getNode('resultVariable'))
            ->raw(sprintf('] = %s::paginateQuery(', Template::class))
            ->subcompile($this->getNode('query'))
            ->raw(");\n");
    }
}
