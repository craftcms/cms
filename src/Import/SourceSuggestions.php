<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Support\Facades\Import;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Ui\Controls\Concerns\HasTextExpander;

use function CraftCms\Cms\t;

/**
 * Suggests an import step's data source as its path is typed.
 *
 * `./` starts in the project root, which relative sources resolve against, `@` offers the aliases, and
 * once a path reaches a folder, its subfolders and the files that can be imported are offered from there.
 *
 * @phpstan-import-type TextExpanderOption from HasTextExpander
 *
 * @since 6.0.0
 */
class SourceSuggestions
{
    /**
     * The text expander triggers for a data source field, fetching from `$source`.
     *
     * @return list<array{trigger: string, boundary: 'start', label: string, source: string, pattern: string}>
     */
    public static function triggers(string $source): array
    {
        // a path's query runs on past each `/`, up to the next whitespace
        $pattern = '[^\s]*';

        return [
            ['trigger' => './', 'boundary' => 'start', 'label' => t('Files'), 'source' => $source, 'pattern' => $pattern],
            ['trigger' => '@', 'boundary' => 'start', 'label' => t('Aliases'), 'source' => $source, 'pattern' => $pattern],
        ];
    }

    /**
     * @param  string  $query  What's been typed, trigger included, e.g. `./`, `@ro` or `@root/resources/my`.
     * @return list<TextExpanderOption>
     */
    public function suggest(string $query, int $limit = 50): array
    {
        // a path that climbs out of where it starts isn't offered, whatever it resolves to
        if (preg_match('#(^|/)\.\.(/|$)#', $query)) {
            return [];
        }

        if (str_starts_with($query, '@') && ! str_contains($query, '/')) {
            return array_slice($this->aliases(substr($query, 1)), 0, $limit);
        }

        $slash = strrpos($query, '/');

        if ($slash === false) {
            return [];
        }

        $folder = substr($query, 0, $slash + 1);
        $name = substr($query, $slash + 1);

        $directory = str_starts_with($folder, '@')
            ? Aliases::get(rtrim($folder, '/'), false)
            : Aliases::get('@root').'/'.ltrim(substr($folder, 2), '/');

        if (! is_string($directory) || Url::isAbsoluteUrl($directory)) {
            return [];
        }

        return array_slice($this->entries($directory, $folder, $name), 0, $limit);
    }

    /**
     * The aliases a source can start with, named like `$search`.
     *
     * @return list<TextExpanderOption>
     */
    private function aliases(string $search): array
    {
        $options = [];

        foreach (Aliases::getAll() as $alias => $path) {
            if ($search !== '' && ! str_contains(strtolower($alias), strtolower($search))) {
                continue;
            }

            if (Url::isAbsoluteUrl($path)) {
                // a URL can be a source, but there's nothing under it to suggest
                $options[] = ['label' => $alias, 'value' => "$alias/", 'data' => ['hint' => $path]];
            } elseif (is_dir($path) && self::isBrowsable($path)) {
                $options[] = ['label' => $alias, 'value' => "$alias/", 'data' => ['hint' => $path], 'continues' => true];
            }
        }

        usort($options, fn (array $a, array $b) => strcmp($a['label'], $b['label']));

        return $options;
    }

    /**
     * The subfolders and importable files in `$directory` whose names start with `$name`, folders first.
     *
     * @param  string  $prefix  How `$directory` was typed, which each suggestion's value starts with.
     * @return list<TextExpanderOption>
     */
    private function entries(string $directory, string $prefix, string $name): array
    {
        if (! is_dir($directory) || ! self::isBrowsable($directory)) {
            return [];
        }

        $folders = [];
        $files = [];

        foreach (scandir($directory) ?: [] as $entry) {
            if (str_starts_with($entry, '.') || ($name !== '' && ! str_starts_with(strtolower($entry), strtolower($name)))) {
                continue;
            }

            $path = "$directory/$entry";

            if (is_dir($path)) {
                if (self::isBrowsable($path)) {
                    $folders[] = ['label' => "$entry/", 'value' => "$prefix$entry/", 'data' => ['hint' => t('Folder')], 'continues' => true];
                }
            } elseif (Import::getDataTypeFromExtension($path) !== null && BaseImporter::isFilepathAllowed($path)) {
                $files[] = ['label' => $entry, 'value' => "$prefix$entry"];
            }
        }

        return [...$folders, ...$files];
    }

    /**
     * Whether files inside a folder may be imported. Asked of a path inside it, since a folder that merely
     * contains a system folder, such as the project root, is otherwise restricted itself.
     */
    private static function isBrowsable(string $directory): bool
    {
        return BaseImporter::isFilepathAllowed(rtrim($directory, '/').'/x');
    }
}
