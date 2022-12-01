<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\generators;

use Craft;
use craft\console\controllers\MakeController;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use ReflectionClass;
use yii\base\BaseObject;

/**
 * Base generator class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
abstract class BaseGenerator extends BaseObject
{
    /**
     * Returns the CLI-facing name of the generator in kebab-case.
     *
     * This will determine how the generator can be accessed in the CLI. For example, if it returns `widget`,
     * then it will be accessible via `craft make widget`.
     *
     * @return string
     */
    public static function name(): string
    {
        // Use the class name by default
        $classParts = explode('\\', static::class);
        return StringHelper::toKebabCase(array_pop($classParts));
    }

    /**
     * Returns the CLI-facing description of the generator.
     *
     * @return string
     */
    public static function description(): string
    {
        // Use the class docblock description by default
        $ref = new ReflectionClass(static::class);
        $docLines = preg_split('/\R/u', $ref->getDocComment());
        return trim($docLines[1] ?? '', "\t *");
    }

    /**
     * @var MakeController The `MakeController` instance that’s handling the CLI request.
     */
    public MakeController $controller;

    /**
     * @var string The base path to the plugin or module that the maker is working with.
     *
     * This must be set for [[writeToFile()]] and [[writeJson()]] to work.
     */
    protected string $basePath;

    /**
     * Runs the generator command.
     *
     * @return int The command exit code to return.
     */
    abstract public function run(): int;

    /**
     * Prompts the user for the base location that a plugin or module should be generated in.
     *
     * @param string $text The prompt string
     * @param string $default The default location to use
     * @param string|null $relativeTo A location that the returned path should be relative to
     * @return string
     */
    protected function basePathPrompt(string $text, string $default, ?string $relativeTo = null): string
    {
        $default = FileHelper::normalizePath(Craft::getAlias($default), '/');
        $relativeTo = FileHelper::normalizePath(Craft::getAlias($relativeTo ?? '@root'), '/');

        $path = $this->controller->prompt($text, [
            'default' => FileHelper::relativePath($default, $relativeTo),
            'validator' => function(string $input, ?string &$error) use ($relativeTo) {
                $path = FileHelper::normalizePath($input, '/');

                if (!str_starts_with($path, '/')) {
                    $path = "$relativeTo/$path";
                }

                if (is_file($path)) {
                    $error = 'A file already exists there.';
                    return false;
                }

                if (is_dir($path) && !FileHelper::isDirectoryEmpty($path)) {
                    $error = 'A non-empty directory already exists there.';
                    return false;
                }

                return true;
            },
        ]);

        return FileHelper::relativePath($path, $relativeTo);
    }

    /**
     * Writes contents to a file, and outputs to the console.
     *
     * @param string $file The path to the file to write to
     * @param string $contents The file contents
     * @param array $options Options for [[\craft\helpers\FileHelper::writeToFile()]]
     */
    protected function writeToFile(string $file, string $contents, array $options = []): void
    {
        $this->controller->writeToFile("$this->basePath/$file", $contents, $options);
    }

    /**
     * JSON-encodes a value and writes it to a file.
     *
     * @param string $file The path to the file to write to
     * @param mixed $value The value to be JSON-encoded and written out
     */
    protected function writeJson(string $file, mixed $value): void
    {
        $json = Json::encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        $this->writeToFile($file, $json);
    }
}
