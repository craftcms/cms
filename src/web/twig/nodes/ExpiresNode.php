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
        $durationNum = $this->getAttribute('durationNum');

        $duration = $durationNum === null ? null : DateTimeHelper::humanDurationToSeconds(
            $durationNum,
            $this->getAttribute('durationUnit'),
        );
        $line = sprintf(
            '\Craft::$app->getResponse()->setCacheHeaders(%s);',
            $duration,
        );

        $compiler->write("$line\n");
    }
}
