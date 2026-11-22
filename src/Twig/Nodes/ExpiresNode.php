<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\ResponseHeaders;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * @since 6.0.0
 */
#[YieldReady]
class ExpiresNode extends Node
{
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $expiration = $this->hasNode('expiration') ? $this->getNode('expiration') : null;

        if ($expiration) {
            $compiler
                ->write('$expiration = ')
                ->subcompile($expiration)
                ->raw(";\n")
                /** @see DateTimeHelper::toDateTime() */
                ->write(sprintf(
                    '$duration = %s::toDateTime($expiration)->getTimestamp() - now()->getTimestamp();',
                    DateTimeHelper::class,
                ));
        } elseif (! $this->hasAttribute('durationNum')) {
            $compiler->write("\$duration = 0;\n");
        } else {
            $now = now();
            $duration = (int) $now->diffInSeconds((clone $now)->add(
                $this->getAttribute('durationNum'),
                $this->getAttribute('durationUnit'),
            ));
            $compiler->write("\$duration = $duration;\n");
        }

        /** @see \CraftCms\Cms\Http\ResponseHeaders::setCache() */
        $compiler->write(ResponseHeaders::class."::setCache(\$duration);\n");
    }
}
