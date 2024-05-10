<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use DateTimeImmutable;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class CacheResponseNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.10.0
 */
class CacheResponseNode extends Node
{
    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        $duration = self::durationInSeconds(
            $this->getAttribute('durationNum'),
            $this->getAttribute('durationUnit'),
        );
        $line = sprintf(
            '\Craft::$app->getResponse()->setCacheHeaders(%s);',
            $duration,
        );

        $compiler->write("$line\n");
    }

    private static function durationInSeconds($number, $unit): int
    {
        if ($unit === 'week') {
            if ($number == 1) {
                $number = 7;
                $unit = 'days';
            } else {
                $unit = 'weeks';
            }
        }

        $now = new DateTimeImmutable();
        $then = $now->modify("+$number $unit");

        return $then->getTimestamp() - $now->getTimestamp();
    }
}
