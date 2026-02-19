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
 * Class PreloadSinglesNode.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.4.0
 */
#[YieldReady]
class PreloadSinglesNode extends Node
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function compile(Compiler $compiler): void
    {
        if (! $this->hasAttribute('handles')) {
            return;
        }

        $compiler
            ->write(sprintf(
                "%s::preloadSingles([%s]);\n",
                Template::class,
                implode(', ', array_map(fn (string $handle) => "'$handle'", $this->getAttribute('handles'))),
            ));
    }
}
