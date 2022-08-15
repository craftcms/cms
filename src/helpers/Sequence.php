<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\db\Query;
use craft\db\Table;
use Throwable;
use yii\db\Exception;

/**
 * Class Sequence
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.31
 */
class Sequence
{
    /**
     * Returns the current value in a given sequence.
     *
     * @param string $name The sequence name.
     * @param int|null $length The minimum string length that should be returned. (Numbers that are too short will be left-padded with `0`s.)
     * @return int|string
     * @since 3.0.32
     */
    public static function current(string $name, ?int $length = null): int|string
    {
        $next = self::_next($name);
        return self::_format($next - 1, $length);
    }

    /**
     * Returns the next number in a given sequence.
     *
     * @param string $name The sequence name.
     * @param int|null $length The minimum string length that should be returned. (Numbers that are too short will be left-padded with `0`s.)
     * @return int|string
     * @throws Exception if a lock could not be acquired for the sequence
     * @throws Throwable if reasons
     */
    public static function next(string $name, ?int $length = null): int|string
    {
        $mutex = Craft::$app->getMutex();
        $lockName = 'seq--' . str_replace(['/', '\\'], '-', $name);

        if (!$mutex->acquire($lockName, 3)) {
            throw new Exception('Could not acquire a lock for the sequence "' . $name . '".');
        }

        try {
            $num = self::_next($name);

            if ($num === 1) {
                Db::insert(Table::SEQUENCES, [
                    'name' => $name,
                    'next' => $num + 1,
                ]);
            } else {
                Db::update(Table::SEQUENCES, [
                    'next' => $num + 1,
                ], [
                    'name' => $name,
                ]);
            }
        } catch (Throwable $e) {
            $mutex->release($lockName);
            throw $e;
        }

        $mutex->release($lockName);
        return self::_format($num, $length);
    }

    /**
     * Returns the next value in the given sequence, without incrementing it.
     *
     * @param string $name
     * @return int
     */
    private static function _next(string $name): int
    {
        return (int)(new Query())
            ->select(['next'])
            ->from(Table::SEQUENCES)
            ->where(['name' => $name])
            ->scalar() ?: 1;
    }

    /**
     * Possibly formats a number based on the given length.
     *
     * @param int $num
     * @param int|null $length
     * @return int|string
     */
    private static function _format(int $num, ?int $length = null): int|string
    {
        if ($length !== null) {
            return str_pad((string)$num, $length, '0', STR_PAD_LEFT);
        }
        return $num;
    }
}
