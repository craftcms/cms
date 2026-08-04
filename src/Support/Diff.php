<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use Symfony\Component\Yaml\Yaml;

class Diff
{
    /**
     * Generates a diff for two values, represented as YAML.
     *
     * @param  int  $indent  The indent size that nested values should have
     * @param  int  $contextLines  The number of lines to show before and after changes
     */
    public static function diff(mixed $from, mixed $to, int $indent = 2, int $contextLines = 3): string
    {
        $diff = '';
        $lines = self::_diff($from, $to, $indent, 0);
        $lastChange = null;

        foreach ($lines as $i => $line) {
            if ($line[0] === null) {
                continue;
            }

            // Is this the first change we've seen?
            if ($contextLines > 0) {
                if ($lastChange === null) {
                    for ($j = max($i - $contextLines, 0); $j < $i; $j++) {
                        $diff .= '  '.$lines[$j][1]."\n";
                    }
                } elseif ($lastChange < $i - $contextLines * 2 + 2) {
                    // More than 2X the context size
                    for ($j = $lastChange + 1; $j < $lastChange + $contextLines + 1; $j++) {
                        $diff .= '  '.$lines[$j][1]."\n";
                    }
                    $diff .= "...\n";
                    for ($j = $i - $contextLines; $j < $i; $j++) {
                        $diff .= '  '.$lines[$j][1]."\n";
                    }
                } else {
                    // Within two contexts so just show the whole chunk
                    for ($j = $lastChange + 1; $j < $i; $j++) {
                        $diff .= '  '.$lines[$j][1]."\n";
                    }
                }
            }

            $diff .= $line[0].' '.$line[1]."\n";
            $lastChange = $i;
        }

        // Remaining context
        if ($lastChange !== null && $contextLines > 0) {
            $max = min($lastChange + $contextLines, count($lines) - 1);
            for ($i = $lastChange + 1; $i < $max; $i++) {
                $diff .= '  '.$lines[$i][1]."\n";
            }
        }

        return rtrim($diff);
    }

    private static function _diff(mixed $from, mixed $to, int $indent, int $level): array
    {
        // Are we done doing recursion?
        if (
            (! is_array($from) || ! Arr::isAssoc($from)) ||
            (! is_array($to) || ! Arr::isAssoc($to))
        ) {
            if (self::compare($from, $to)) {
                return self::_buildLinesForValue($from, $indent, $level);
            }

            $lines = [];
            array_push($lines, ...self::_buildLinesForValue($from, $indent, $level, '-'));
            array_push($lines, ...self::_buildLinesForValue($to, $indent, $level, '+'));

            return $lines;
        }

        $lines = [];
        $toKeys = array_keys($to);
        $toCursor = 0;

        foreach ($from as $key => $value) {
            // Do both arrays have this key?
            if (array_key_exists($key, $to)) {
                $toPos = array_search($key, $toKeys);

                // Output any keys in $to that come before this one
                if ($toPos > $toCursor) {
                    $newKeys = array_slice($toKeys, $toCursor, $toPos - $toCursor);
                    array_push($lines, ...self::_buildLinesForValue(Arr::only($to, $newKeys), $indent, $level, '+'));
                }

                $lines[] = self::_buildLine("$key:", $indent, $level);
                array_push($lines, ...self::_diff($value, $to[$key], $indent, $level + 1));
                $toCursor = $toPos + 1;
            } else {
                array_push($lines, ...self::_buildLinesForValue([$key => $value], $indent, $level, '-'));
            }
        }

        // Output any remaining $to keys
        $newKeys = array_slice($toKeys, $toCursor);
        if (! empty($newKeys)) {
            array_push($lines, ...self::_buildLinesForValue(Arr::only($to, $newKeys), $indent, $level, '+'));
        }

        return $lines;
    }

    private static function _buildLinesForValue($value, int $indent, int $level, ?string $char = null): array
    {
        return str(Yaml::dump($value, 20 - $level, $indent))
            ->rtrim()
            ->explode("\n")
            ->map(fn (string $line) => self::_buildLine($line, $indent, $level, $char))
            ->all();
    }

    private static function _buildLine(string $line, int $indent, int $level, ?string $char = null): array
    {
        return [$char, str_repeat(' ', $indent * $level).$line];
    }

    /**
     * Compares two arrays and returns whether they are identical.
     *
     * If the values are both arrays, they will be compared recursively.
     *
     * @param  bool  $strict  Whether strict comparisons should be used
     */
    public static function compare(mixed $a, mixed $b, bool $strict = true): bool
    {
        if (! is_array($a) || ! is_array($b)) {
            return $strict ? $a === $b : $a == $b;
        }

        if (array_keys($a) !== array_keys($b)) {
            return false;
        }

        return array_all($a, fn ($value, $key) => self::compare($value, $b[$key], $strict));
    }
}
