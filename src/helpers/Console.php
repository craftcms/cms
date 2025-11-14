<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\console\MarkdownParser;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;
use yii\console\Controller;
use const STDOUT;

/**
 * Console helper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Console extends \yii\helpers\Console
{
    /**
     * @var int The number of times [[output()]] has been called.
     * @since 5.5.0
     */
    public static int $outputCount = 0;

    /**
     * @var bool Whether a newline should be prepended to the next output.
     * @since 5.5.0
     */
    public static bool $prependNewline = false;

    private static int $indent = 0;

    /**
     * Increases the indent prepended to [[output()]] strings.
     *
     * @since 5.5.0
     */
    public static function indent(): void
    {
        self::$indent++;
    }

    /**
     * Decreases the indent prepended to [[output()]] strings.
     *
     * @since 5.5.0
     */
    public static function outdent(): void
    {
        self::$indent = max(0, self::$indent - 1);
    }

    /**
     * Returns the indent string that should be appended to output lines.
     *
     * @return string
     * @since 5.5.0
     */
    public static function indentStr(): string
    {
        return str_repeat('   ', self::$indent);
    }

    /**
     * @inheritdoc
     */
    public static function output($string = null): int|bool
    {
        self::$outputCount++;

        if ($string !== null) {
            if (self::$prependNewline) {
                $string = "\n$string";
                self::$prependNewline = false;
            }

            if (self::$indent !== 0) {
                $lines = StringHelper::lines($string);
                $lines = array_map(fn(string $line) => static::indentStr() . $line, $lines);
                $string = implode("\n", $lines);
            }
        }

        return parent::output($string);
    }

    /**
     * Prints a string to STDOUT.
     *
     * You may optionally format the string with ANSI codes by
     * passing additional parameters using the constants defined in [[\yii\helpers\Console]].
     * Example:
     *
     * ```php
     * Console::stdout('This will be red and underlined.', Console::FG_RED, Console::UNDERLINE);
     * ```
     *
     * @param string $string the string to print
     * @return int|false Number of bytes printed or false on error
     */
    public static function stdout($string): int|false
    {
        self::$outputCount++;

        if (self::$prependNewline) {
            $string = "\n$string";
            self::$prependNewline = false;
        }

        if (static::streamSupportsAnsiColors(STDOUT)) {
            $args = func_get_args();
            array_shift($args);
            if (!empty($args)) {
                $string = self::ansiFormat($string, $args);
            }
        }

        return parent::stdout($string);
    }

    /**
     * Returns whether color is enabled.
     *
     * @return bool
     * @since 3.0.38
     */
    public static function isColorEnabled(): bool
    {
        $controller = Craft::$app->controller;
        return $controller instanceof Controller && $controller->isColorEnabled();
    }

    /**
     * @inheritdoc
     */
    public static function markdownToAnsi($markdown)
    {
        $parser = new MarkdownParser();
        return $parser->parse($markdown);
    }

    /**
     * Outputs a terminal command.
     *
     * @param string $command The command to output
     * @param bool $withScriptName Whether the current script name (e.g. `craft`) should be prepended to the command.
     * @since 3.0.38
     */
    public static function outputCommand(string $command, bool $withScriptName = true): void
    {
        if ($withScriptName) {
            try {
                $file = Craft::$app->getRequest()->getScriptFilename();
            } catch (InvalidConfigException) {
                $file = 'craft';
            }
            $command = $file . ' ' . $command;
        }

        if (static::isColorEnabled()) {
            static::stdout($command, self::FG_CYAN);
        } else {
            static::stdout("`$command`");
        }
    }

    /**
     * Outputs a warning.
     *
     * @param string $text
     * @param bool $center
     * @since 3.0.38
     */
    public static function outputWarning(string $text, bool $center = true): void
    {
        $xPad = 4;
        $lines = explode("\n", $text);
        $width = 0;
        foreach ($lines as $line) {
            $width = max($width, strlen($line));
        }

        $isColorEnabled = static::isColorEnabled();
        $format = $isColorEnabled ? [self::BG_RED, self::BOLD] : [];

        static::output();

        if ($isColorEnabled) {
            static::output(static::ansiFormat(str_repeat(' ', $width + $xPad * 2), $format));
        }

        foreach ($lines as $line) {
            $extra = $width - strlen($line);
            if ($center) {
                static::output(static::ansiFormat(str_repeat(' ', (int)floor($extra / 2) + $xPad) . $line . str_repeat(' ', (int)ceil($extra / 2) + $xPad), $format));
            } else {
                static::output(static::ansiFormat(str_repeat(' ', $xPad) . $line . str_repeat(' ', $extra + $xPad), $format));
            }
        }

        if ($isColorEnabled) {
            static::output(static::ansiFormat(str_repeat(' ', $width + $xPad * 2), $format));
        }

        static::output();
    }

    /**
     * Outputs a table.
     *
     * `$data` should be set to an array of nested arrays. Each nested array should contain values for the
     * same keys found in `$headers`.
     *
     * Header and data values can be expressed as a string (the raw value), or an array that begins with the
     * raw value, followed by any of the following keys:
     *
     * - `align` – either `left`, `right`, or `center` (defaults to `left`).
     * - `format` – an array that should be passed to [[ansiFormat()]].
     *
     * `$options` supports the following:
     *
     * - `maxSize` – The maximum number of characters to show within each cell (defaults to 80).
     * - `rowPrefix` - any characters that should be output before each row (defaults to four spaces)
     * - `rowSuffix – any characters that should be output after each row
     * - `colors` – Whether to format cells per their `format` keys (defaults to [[streamSupportsAnsiColors()]]).
     *
     * @param string[]|array[] $headers The table headers
     * @param array[] $data The table data
     * @param array $options
     * @throws InvalidValueException if an `align` value is invalid
     * @since 3.7.23
     */
    public static function table(array $headers, array $data, array $options = []): void
    {
        $options += [
            'maxSize' => 80,
            'rowPrefix' => '   ',
            'rowSuffix' => '',
            'colors' => static::streamSupportsAnsiColors(STDOUT),
        ];

        $keys = array_keys($headers);

        // Figure out the max col sizes
        $cellSizes = [];
        foreach (array_merge($data, [$headers]) as $row) {
            foreach ($keys as $key) {
                $cell = $row[$key];
                $value = is_array($cell) ? reset($cell) : $cell;
                $cellSizes[$key][] = mb_strlen(static::stripAnsiFormat($value));
            }
        }

        $maxCellSizes = [];
        foreach ($cellSizes as $key => $sizes) {
            $maxCellSizes[$key] = min(max($sizes), $options['maxSize']);
        }

        self::_tableRow($headers, $maxCellSizes, $options);

        self::_tableRow(array_map(fn(int $size) => str_repeat('-', $size), $maxCellSizes), $maxCellSizes, $options);

        foreach ($data as $row) {
            self::_tableRow($row, $maxCellSizes, $options);
        }
    }

    /**
     * @param array $row
     * @param int[] $sizes
     * @param array $options
     * @throws InvalidValueException
     */
    private static function _tableRow(array $row, array $sizes, array $options): void
    {
        $output = [];

        foreach ($sizes as $key => $size) {
            $cell = $row[$key] ?? '';
            $value = is_array($cell) ? reset($cell) : $cell;
            $len = mb_strlen(static::stripAnsiFormat($value));

            if ($len < $size) {
                $padSize = $size - $len;
                $value = match ($cell['align'] ?? null) {
                    'right' => sprintf('%s%s', str_repeat(' ', $padSize), $value),
                    'center' => sprintf(
                        '%s%s%s',
                        str_repeat(' ', (int)floor($padSize / 2)),
                        $value,
                        str_repeat(' ', (int)ceil($padSize / 2)),
                    ),
                    default => sprintf('%s%s', $value, str_repeat(' ', $padSize)),
                };
            } elseif ($len > $size) {
                $value = mb_substr($value, 0, $size - 1) . '…';
            }

            if (isset($cell['format']) && $options['colors']) {
                $value = Console::ansiFormat($value, $cell['format']);
            }

            $output[] = $value;
        }

        static::stdout($options['rowPrefix'] . implode('  ', $output) . $options['rowSuffix'] . PHP_EOL);
    }

    /**
     * Ensures that the project config YAML files exist if they’re supposed to
     *
     * @since 3.5.0
     */
    public static function ensureProjectConfigFileExists(): void
    {
        $projectConfig = Craft::$app->getProjectConfig();

        if ($projectConfig->writeYamlAutomatically && !$projectConfig->getDoesExternalConfigExist()) {
            static::stdout('Generating project config files from the loaded project config ... ', static::FG_YELLOW);
            $projectConfig->regenerateExternalConfig();
            static::stdout('done' . PHP_EOL, static::FG_GREEN);
        }
    }
}
