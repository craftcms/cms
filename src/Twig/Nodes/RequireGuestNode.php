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
 * Class RequireGuestNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.4.0
 */
#[YieldReady]
class RequireGuestNode extends Node
{
    /**
     * Compiles a RequireGuestNode into PHP.
     */
    #[\Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write(Craft::class."::\$app->controller->requireGuest();\n");
    }
}
