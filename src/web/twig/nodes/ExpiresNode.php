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

        $duration = $durationNum === null ? null : self::durationInSeconds(
            $durationNum,
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
