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
            'rowPrefix' => '    ',
            'rowSuffix' => '',
            'colors' => static::streamSupportsAnsiColors(STDOUT),
        ];

        $keys = array_keys($headers);

        // Figure out the max col sizes
        $cellSizes = [];
        foreach (array_merge($data, [$headers]) as $row) {
            foreach ($keys as $key) {
                $cell = $row[$key];
                $cellSizes[$key][] = mb_strlen(is_array($cell) ? reset($cell) : $cell);
            }
        }

        $maxCellSizes = [];
        foreach ($cellSizes as $key => $sizes) {
            $maxCellSizes[$key] = min(max($sizes), $options['maxSize']);
        }

        self::_tableRow($headers, $maxCellSizes, $options);

        self::_tableRow(array_map(function(int $size) {
            return str_repeat('-', $size);
        }, $maxCellSizes), $maxCellSizes, $options);

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
            $len = strlen($value);

            if ($len < $size) {
                if (isset($cell['align'])) {
                    $padType = match ($cell['align']) {
                        'left' => STR_PAD_RIGHT,
                        'right' => STR_PAD_LEFT,
                        'center' => STR_PAD_BOTH,
                        default => throw new InvalidValueException("Invalid align value: {$cell['align']}"),
                    };
                } else {
                    $padType = STR_PAD_RIGHT;
                }

                $value = str_pad($value, $size, ' ', $padType);
            } elseif ($len > $size) {
                $value = substr($value, 0, $size - 1) . '…';
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
