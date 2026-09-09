<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig;

/** @internal */
class ConfigChanges
{
    /** @return array<string, mixed> */
    public static function leaves(mixed $value, string $path = ''): array
    {
        if (! is_array($value)) {
            return $value === null ? [] : [$path => $value];
        }

        $leaves = [];
        ProjectConfigHelper::flattenConfigArray($value, $path, $leaves);

        return $leaves;
    }

    /** @return array{added?: array<string, mixed>, removed?: array<string, mixed>, message?: string} */
    public static function history(string $path, mixed $oldValue, mixed $newValue, ?string $message): array
    {
        $change = [];

        if (ProjectConfigHelper::encodeValueAsString($oldValue) !== ProjectConfigHelper::encodeValueAsString($newValue)) {
            if ($newValue !== null) {
                $change['added'] = self::leaves($newValue, $path);
            }

            if ($oldValue !== null) {
                $change['removed'] = self::leaves($oldValue, $path);
            }
        }

        if ($message !== null && $message !== '') {
            $change['message'] = $message;
        }

        return $change;
    }

    /**
     * @param  array<string|int, mixed>  $old
     * @param  array<string|int, mixed>  $new
     * @return array{newItems: list<string>, removedItems: list<string>, changedItems: list<string>}
     */
    public static function pending(array $old, array $new, bool $force = false): array
    {
        $old = self::leaves($old);
        $new = self::leaves($new);
        $paths = ['newItems' => [], 'removedItems' => [], 'changedItems' => []];

        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $path) {
            $category = match (true) {
                ! array_key_exists($path, $old) => 'newItems',
                ! array_key_exists($path, $new) => 'removedItems',
                $force || $old[$path] !== $new[$path] => 'changedItems',
                default => null,
            };

            if ($category !== null) {
                // Changes to leaf values are processed at their immediate parent path.
                $parent = ProjectConfigHelper::pathWithoutLastSegment($path);
                $paths[$category][] = $parent ?? $path;
            }
        }

        foreach ($paths as &$category) {
            $category = array_values(array_unique($category));

            // Group paths by similarity, sorted by depth (descending), e.g.:
            // - foo1.bar.baz
            // - foo1.bar
            // - foo2.bar.baz
            // - foo2.bar
            usort($category, function (string $left, string $right): int {
                $leftSegments = ProjectConfigHelper::pathSegments($left);
                $rightSegments = ProjectConfigHelper::pathSegments($right);
                $sharedDepth = min(count($leftSegments), count($rightSegments));

                for ($index = 0; $index < $sharedDepth; $index++) {
                    if ($leftSegments[$index] !== $rightSegments[$index]) {
                        return $leftSegments[$index] <=> $rightSegments[$index];
                    }
                }

                return count($rightSegments) <=> count($leftSegments);
            });
        }

        return $paths;
    }
}
