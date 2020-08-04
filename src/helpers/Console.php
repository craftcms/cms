<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use yii\base\InvalidConfigException;
use yii\console\Controller;

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
     * @return int|bool Number of bytes printed or false on error
     */
    public static function stdout($string)
    {
        if (static::streamSupportsAnsiColors(\STDOUT)) {
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
     * Outputs a terminal command.
     *
     * @param string $command The command to output
     * @param bool $withScriptName Whether the current script name (e.g. `craft`) should be prepended to the command.
     * @since 3.0.38
     */
    public static function outputCommand(string $command, bool $withScriptName = true)
    {
        if ($withScriptName) {
            try {
                $file = Craft::$app->getRequest()->getScriptFilename();
            } catch (InvalidConfigException $e) {
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
     * @since 3.0.38
     */
    public static function outputWarning(string $text)
    {
        $xPad = 4;
        $lines = explode("\n", $text);
        $width = 0;
        foreach ($lines as $line) {
            $width = max($width, strlen($line));
        }
        $width += $xPad * 2;

        $isColorEnabled = static::isColorEnabled();
        $format = $isColorEnabled ? [self::BG_RED, self::BOLD] : [];

        static::output();

        if ($isColorEnabled) {
            static::output(static::ansiFormat(str_repeat(' ', $width), $format));
        }

        foreach ($lines as $line) {
            $extra = $width - strlen($line);
            static::output(static::ansiFormat(str_repeat(' ', floor($extra / 2)) . $line . str_repeat(' ', ceil($extra / 2)), $format));
        }

        if ($isColorEnabled) {
            static::output(static::ansiFormat(str_repeat(' ', $width), $format));
        }

        static::output();
    }

    /**
     * Ensures that the project.yaml file exists if it's supposed to.
     *
     * @since 3.5.0
     */
    public static function ensureProjectConfigFileExists()
    {
        if (!file_exists(Craft::$app->getPath()->getProjectConfigFilePath())) {
            $projectConfig = Craft::$app->getProjectConfig();
            static::stdout('Generating project config files from the loaded project config ... ', static::FG_YELLOW);
            $projectConfig->regenerateYamlFromConfig();
            static::stdout('done' . PHP_EOL, static::FG_GREEN);
        }
    }
}
