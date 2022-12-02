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
use yii\base\InvalidArgumentException;

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
    public string $basePath;

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
     * @return string
     */
    protected function targetDirPrompt(string $text, string $default): string
    {
        $path = $this->controller->prompt($text, [
            'default' => FileHelper::relativePath(Craft::getAlias($default)),
            'validator' => function(string $input, ?string &$error) {
                $path = FileHelper::normalizePath($input, '/');
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

        // Make sure it's absolute
        $path = FileHelper::normalizePath($path, '/');
        if (!str_starts_with($path, '/')) {
            $path = sprintf("%s/%s", FileHelper::normalizePath(getcwd()), $path);
        }

        return $path;
    }

    /**
     * Normalizes a PHP namespace.
     *
     * @param string $namespace
     * @return string
     */
    protected function normalizeNamespace(string $namespace): string
    {
        return trim(preg_replace('/\\\\+/', '\\', $namespace), '\\');
    }

    /**
     * Ensures that a directory is within an autoload root for a given composer.json file,
     * and returns the root namespace for the directory.
     *
     * @param string $composerPath The path to composer.json
     * @param string $dir The directory path
     * @return string
     */
    protected function dirNamespace(string $composerPath, string $dir): string
    {
        $dir = FileHelper::normalizePath($dir, '/');
        $composerDir = FileHelper::normalizePath(dirname(realpath($composerPath)), '/');
        $composerJson = file_get_contents($composerPath);

        try {
            $composerConfig = Json::decode($composerJson);
        } catch (InvalidArgumentException $e) {
            $this->controller->failure("`$composerPath` contains a syntax error.");
            $this->controller->stdout(PHP_EOL);
            throw $e;
        }

        // Check if that path is already getting autoloaded
        $autoloadRoots = $composerConfig['autoload']['psr-4'] ?? [];

        foreach ($autoloadRoots as $autoloadNamespace => $autoloadPath) {
            $autoloadPath = FileHelper::normalizePath($autoloadPath, '/');
            if (!str_starts_with($autoloadPath, '/')) {
                $autoloadPath = "$composerDir/$autoloadPath";
            }
            if (str_starts_with("$dir/", "$autoloadPath/")) {
                $autoloadRelativePath = FileHelper::relativePath($dir, $autoloadPath);
                return $this->normalizeNamespace($autoloadNamespace . '\\' . str_replace('/', '\\', $autoloadRelativePath));
            }
        }

        $composerRelativePath = FileHelper::relativePath($dir, $composerDir);
        $rootNamespace = $this->controller->prompt($this->controller->markdownToAnsi("What should the root namespace for `$composerRelativePath` be?"), [
            'required' => true,
            'pattern' => '/^[a-z\\\\]+$/i',
        ]);
        $rootNamespace = $this->normalizeNamespace($rootNamespace);

        $composerConfig['autoload']['psr-4']["$rootNamespace\\"] = FileHelper::relativePath($dir, $composerDir) . '/';
        $this->controller->writeJson($composerPath, $composerConfig);

        return $rootNamespace;
    }
}
