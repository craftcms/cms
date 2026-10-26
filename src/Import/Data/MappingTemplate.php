<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Data;

use CraftCms\Cms\Import\ImportHelper;

/**
 * An object template a mapped column is filled from, and the incoming data paths it reads.
 *
 * Only the forms the mapping screen's variable suggestions insert are read as paths: `{author.name}`
 * shorthand, and `object.author['First Name']` inside a print tag. Data used any other way, such as in
 * a `{% for %}` loop, isn't, and nor are the reserved keys (`ImportHelper::RESERVED_DATA_KEYS`).
 *
 * @since 6.0.0
 */
readonly class MappingTemplate
{
    /**
     * @var list<string> The incoming data paths the template reads, e.g. `author.name`.
     */
    public array $paths;

    public function __construct(public string $template)
    {
        $this->paths = self::readPaths($template);
    }

    /**
     * @return list<string>
     */
    private static function readPaths(string $template): array
    {
        $paths = [];

        // tags, and everything inside a `{% verbatim %}` block, aren't read
        $template = (string) preg_replace('/\{%-?\s*verbatim\s*-?%\}.*?\{%-?\s*endverbatim\s*-?%\}/s', '', $template);
        $template = (string) preg_replace('/\{%.*?%\}/s', '', $template);

        // print tags, e.g. `{{ object.author['First Name'] }}`, are read and then removed, so their
        // braces aren't mistaken for shorthand below
        $template = (string) preg_replace_callback('/\{\{.*?\}\}/s', function (array $printTag) use (&$paths): string {
            array_push($paths, ...self::objectReferencePaths($printTag[0]));

            return '';
        }, $template);

        array_push($paths, ...self::shorthandPaths($template));

        return array_values(array_unique(array_filter(
            $paths,
            fn (string $path): bool => $path !== '' && ! ImportHelper::isReservedDataPath($path),
        )));
    }

    /**
     * Returns the paths read by `object` references, e.g. `object.author['First Name']` reads `author.First Name`.
     *
     * @return list<string>
     */
    private static function objectReferencePaths(string $printTag): array
    {
        preg_match_all('/\bobject((?:\.\w+|\[([\'"]).*?\2\])+)/', $printTag, $references);

        return array_map(function (string $reference): string {
            preg_match_all('/\.(?<name>\w+)|\[([\'"])(?<quoted>.*?)\2\]/', $reference, $segments, PREG_SET_ORDER);

            return implode('.', array_map(fn (array $segment): string => $segment['quoted'] ?? $segment['name'], $segments));
        }, $references[1]);
    }

    /**
     * Returns the paths read by shorthand tags, e.g. `{author.name|upper}` reads `author.name`.
     *
     * Like `TwigRenderer::normalizeObjectTemplate()`, a function call (`{now()}`) or an object literal (`{a: 1}`)
     * isn't a shorthand tag.
     *
     * @return list<string>
     */
    private static function shorthandPaths(string $template): array
    {
        preg_match_all('/(?<!\{)\{\s*(\w++(?:\.\w++)*+)\s*+(?![(:])[^{]*?\}/', $template, $matches);

        return $matches[1];
    }
}
