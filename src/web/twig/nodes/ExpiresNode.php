<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use craft\helpers\DateTimeHelper;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class ExpiresNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.10.0
 */
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
                ->write('$duration = \craft\helpers\DateTimeHelper::toDateTime($expiration)->getTimestamp() - time();');
        } else {
            $duration = DateTimeHelper::relativeTimeToSeconds(
                $this->getAttribute('durationNum'),
                $this->getAttribute('durationUnit'),
            );
            $compiler->write("\$duration = $duration;\n");
        }

        $compiler
            ->write('\Craft::$app->getResponse()->setCacheHeaders($duration);')
            ->raw("\n");
    }
}
