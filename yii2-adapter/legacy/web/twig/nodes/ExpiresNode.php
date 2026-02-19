<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use Craft;
use craft\helpers\DateTimeHelper;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class ExpiresNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.2.0
 */
#[YieldReady]
class ExpiresNode extends Node
{
    /**
     * @inheritdoc
     */
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
            ->write(Craft::class . "::\$app->getResponse()->setCacheHeaders(\$duration);\n");
    }
}
