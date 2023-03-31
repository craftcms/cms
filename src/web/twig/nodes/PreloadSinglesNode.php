<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use craft\helpers\Template;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class PreloadSinglesNode.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class PreloadSinglesNode extends Node
{
    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        if (!$this->hasAttribute('handles')) {
            return;
        }

        $compiler
            ->write(sprintf(
                "%s::preloadSingles([%s]);\n",
                Template::class,
                implode(', ', array_map(fn(string $handle) => "'$handle'", $this->getAttribute('handles'))),
            ));
    }
}
