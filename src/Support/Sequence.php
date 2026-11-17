<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;

/**
 * @since 6.0.0
 */
readonly class Sequence
{
    /**
     * Returns the current value in a given sequence.
     *
     * @param  string  $name  The sequence name.
     * @param  int|null  $length  The minimum string length that should be returned. (Numbers that are too short will be left-padded with `0`s.)
     */
    public static function current(string $name, ?int $length = null): int|string
    {
        $next = self::nextNumber($name);

        return self::format($next - 1, $length);
    }

    /**
     * Returns the next number in a given sequence.
     *
     * @param  string  $name  The sequence name.
     * @param  int|null  $length  The minimum string length that should be returned. (Numbers that are too short will be left-padded with `0`s.)
     */
    public static function next(string $name, ?int $length = null): int|string
    {
        $name = Str::emojiToShortcodes($name);

        // The update locks the row until the transaction commits, so concurrent callers get distinct numbers
        $next = DB::transaction(function () use ($name) {
            DB::table(Table::SEQUENCES)->insertOrIgnore(['name' => $name, 'next' => 1]);
            DB::table(Table::SEQUENCES)->where('name', $name)->increment('next');

            return self::nextNumber($name);
        });

        return self::format($next - 1, $length);
    }

    /**
     * Returns the next value in the given sequence, without incrementing it.
     */
    private static function nextNumber(string $name): int
    {
        return (int) DB::table(Table::SEQUENCES)
            ->where('name', $name)
            ->value('next') ?: 1;
    }

    private static function format(int $num, ?int $length = null): int|string
    {
        if ($length !== null) {
            return str_pad((string) $num, $length, '0', STR_PAD_LEFT);
        }

        return $num;
    }
}
