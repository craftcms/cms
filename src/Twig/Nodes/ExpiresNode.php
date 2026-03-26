<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use Craft;
use CraftCms\Cms\Support\DateTimeHelper;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

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
                    '$duration = %s::toDateTime($expiration)->getTimestamp() - %s::currentTimeStamp();',
                    DateTimeHelper::class,
                    DateTimeHelper::class,
                ));
        } else {
            $duration = DateTimeHelper::relativeTimeToSeconds(
                $this->getAttribute('durationNum'),
                $this->getAttribute('durationUnit'),
            );
            $compiler->write("\$duration = $duration;\n");
        }

        $compiler
            ->write(Craft::class."::\$app->getResponse()->setCacheHeaders(\$duration);\n");
    }
}
