<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\Twig\Nodes;

use Craft;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class RequireAdminNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 */
#[YieldReady]
class RequireAdminNode extends Node
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write(Craft::class.'::$app->controller->requireAdmin(');

        if ($this->hasNode('requireAdminChanges')) {
            $compiler->subcompile($this->getNode('requireAdminChanges'));
        }

        $compiler->raw(");\n");
    }
}
