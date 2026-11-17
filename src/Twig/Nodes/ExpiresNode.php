<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\Deprecator;
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
                ->write(sprintf(
                    '$duration = %s::toDateTime($expiration)->getTimestamp() - now()->getTimestamp();',
                    DateTimeHelper::class,
                ));
        } elseif (! $this->hasAttribute('durationNum')) {
            $compiler
                ->write(sprintf(
                    "%s::log('expires-tag-without-duration', %s);\n",
                    Deprecator::class,
                    var_export('`{% expires %}` without a duration has been deprecated. `{% do ResponseHeaders.noCache() %}` should be used instead.', true),
                ))
                ->write("\$duration = 0;\n");
        } else {
            $now = now();
            $duration = (int) $now->diffInSeconds((clone $now)->add(
                $this->getAttribute('durationNum'),
                $this->getAttribute('durationUnit'),
            ));
            $compiler->write("\$duration = $duration;\n");
        }

        $compiler->write(ResponseHeaders::class."::setCache(\$duration);\n");
    }
}
