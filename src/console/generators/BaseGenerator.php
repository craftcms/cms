<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\generators;

use Craft;
use craft\console\controllers\MakeController;
use craft\helpers\App;
use craft\helpers\Composer;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use Generator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Factory;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use yii\base\BaseObject;
use yii\base\InvalidArgumentException;
use yii\base\Module as BaseModule;
use yii\base\NotSupportedException;

/**
 * Base generator class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
abstract class BaseGenerator extends BaseObject
{
    protected const CLASS_CONSTANTS = 'constants';
    protected const CLASS_PROPERTIES = 'properties';
    protected const CLASS_METHODS = 'methods';

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
     * @var BaseModule The module that the generator is working with, if not the Craft project itself.
     */
    public ?BaseModule $module;

    /**
     * @var string The base path that the generator is working with.
     */
    public string $basePath;

    /**
     * @var string|null The base namespace that the generator is working with.
     *
     * This will be set for all module and plugin component generators.
     */
    public ?string $baseNamespace;

    /**
     * @var string The path to `composer.json`.
     */
    public string $composerFile;

    /**
     * Runs the generator command.
     *
     * @return bool Whether the generator was successful
     */
    abstract public function run(): bool;

    /**
     * Prompts the user for a PHP namespace.
     *
     * If [[baseNamespace]] is set, only namespaces within it will be allowed.
     *
     * @param string $text The prompt text
     * @param array $options Prompt options:
     *
     * - `required` (bool): whether a value is required
     * - `default` (string): the default value to use if no input is given
     * - `validator` (callable): a callable function to validate input. The function must accept two parameters:
     *     - `$namespace`: a normalized namespace based on the input value
     *     - `$error`: passed by reference, to be set to the error text if validation failed
     *
     * @return string|null The normalized namespace
     */
    protected function namespacePrompt(string $text, array $options = []): ?string
    {
        if (isset($options['pattern'])) {
            throw new NotSupportedException('`pattern` is not supported by `namespacePrompt()`.');
        }

        if (isset($options['default'])) {
            $options['default'] = App::normalizeNamespace($options['default']);
            if ($this->baseNamespace && !str_starts_with("{$options['default']}\\", "$this->baseNamespace\\")) {
                throw new InvalidArgumentException("The default value must begin with the base namespace ($this->baseNamespace).");
            }
        }

        $namespace = $this->controller->prompt($this->controller->markdownToAnsi($text), [
            'validator' => function(string $input, ?string &$error) use ($options): bool {
                try {
                    $namespace = App::normalizeNamespace($input);
                } catch (InvalidArgumentException) {
                    $error = 'Invalid namespace';
                    return false;
                }
                if ($this->baseNamespace && !str_starts_with("$namespace\\", "$this->baseNamespace\\")) {
                    $error = $this->controller->markdownToAnsi("The namespace must begin with `$this->baseNamespace`.");
                    return false;
                }
                if (isset($options['validator'])) {
                    return $options['validator']($namespace, $error);
                }
                return true;
            },
        ] + $options);

        if (!$namespace) {
            return null;
        }

        return App::normalizeNamespace($namespace);
    }

    /**
     * Prompts the user for the path to a directory.
     *
     * @param string $text The prompt text
     * @param array $options Prompt options:
     *
     * - `required` (bool): whether a value is required
     * - `default` (string): the default value to use if no input is given
     * - `ensureEmpty` (bool): whether the directory must be empty, if it exists already
     * - `validator` (callable): a callable function to validate input. The function must accept two parameters:
     *     - `$path`: a normalized absolute path based on the input value
     *     - `$error`: passed by reference, to be set to the error text if validation failed
     *
     * @return string|null the normalized absolute path, or `null`
     */
    protected function directoryPrompt(string $text, array $options = []): ?string
    {
        if (isset($options['pattern'])) {
            throw new NotSupportedException('`pattern` is not supported by `directoryPrompt()`.');
        }

        $validate = function(string $input, ?string &$error) use ($options): bool {
            $path = FileHelper::absolutePath($input, ds: '/');
            if (is_file($path)) {
                $error = 'A file already exists there.';
                return false;
            }
            if (!empty($options['ensureEmpty']) && is_dir($path) && !FileHelper::isDirectoryEmpty($path)) {
                $error = 'A non-empty directory already exists there.';
                return false;
            }
            if (isset($options['validator'])) {
                return $options['validator']($path, $error);
            }
            return true;
        };

        if (isset($options['default'])) {
            $options['default'] = FileHelper::relativePath(Craft::getAlias($options['default']));

            // Make sure the default directory is valid before we suggest it
            if (!$validate($options['default'], $error)) {
                unset($options['default']);
                $options['required'] = true;
            }
        }

        $path = $this->controller->prompt($text, [
            'validator' => $validate,
        ] + $options);

        if (!$path) {
            return null;
        }

        return FileHelper::absolutePath($path, ds: '/');
    }

    /**
     * Prompts the user for the path to an autoloadable directory.
     *
     * @param string $text The prompt text
     * @param array $options Prompt options:
     *
     * - `default` (string): the default value to use if no input is given
     * - `ensureEmpty` (bool): whether the directory must be empty, if it exists already
     * - `validator` (callable): a callable function to validate input. The function must accept two parameters:
     *     - `$path`: a normalized absolute path based on the input value
     *     - `$error`: passed by reference, to be set to the error text if validation failed
     *
     * @return array the normalized absolute path to the directory, its root namespace, and whether a new autoload root was added.
     * @phpstan-return array{string,string,bool}
     */
    protected function autoloadableDirectoryPrompt(string $text, array $options): array
    {
        $dir = $this->directoryPrompt($text, [
            'required' => true,
            'validator' => function(string $path, ?string &$error) use ($options): bool {
                if (!Composer::couldAutoload($path, $this->composerFile, reason: $reason)) {
                    $error = $this->controller->markdownToAnsi($reason);
                    return false;
                }
                if (isset($options['validator'])) {
                    return $options['validator']($path, $error);
                }
                return true;
            },
        ] + $options);

        [$namespace, $addedRoot] = $this->ensureAutoloadable($dir);
        return [$dir, $namespace, $addedRoot];
    }

    /**
     * Ensures that a directory is autoloadable in composer.json,
     * and returns the root namespace for the directory.
     *
     * @param string $dir The directory path
     * @return array The root namespace, and whether a new autoload root was added
     * @phpstan-return array{string,bool}
     */
    protected function ensureAutoloadable(string $dir): array
    {
        $dir = FileHelper::absolutePath($dir, ds: '/');

        if (!Composer::couldAutoload($dir, $this->composerFile, $existingRoot, $reason)) {
            throw new InvalidArgumentException($reason);
        }

        if ($existingRoot) {
            [$rootNamespace, $rootPath] = $existingRoot;

            if ($dir === $rootPath) {
                return [rtrim($rootNamespace, '\\'), false];
            }

            $relativePath = FileHelper::relativePath($dir, $rootPath);
            return [$rootNamespace . App::normalizeNamespace($relativePath), false];
        }

        $composerDir = dirname(FileHelper::absolutePath($this->composerFile, ds: '/'));
        $newRootPath = FileHelper::relativePath($dir, $composerDir) . '/';

        $newRootNamespace = $this->namespacePrompt("What should the root namespace for `$newRootPath` be?", [
            'required' => true,
        ]);

        $composerConfig = Json::decodeFromFile($this->composerFile);
        $composerConfig['autoload']['psr-4']["$newRootNamespace\\"] = $newRootPath;
        $this->controller->writeJson($this->composerFile, $composerConfig);

        return [$newRootNamespace, true];
    }

    /**
     * Creates a new [[ClassType]] that extends a given base class, and populates it with some of its
     * constants, properties, and methods.
     *
     * @param string|null $className The class name
     * @param string|null $baseClass The base class
     * @param array $members Class members from the base class that should be added:
     *
     * - `constants`: Array of constant names. You can use key/value pairs to override default values.
     * - `properties`: Array of property names. You can use key/value pairs to override default values.
     * - `methods`: Array of method names. You can use key/value pairs to override the method bodies.
     *
     * Note that if any constants, properties, or method parameters are set to a constant, the constant’s *value* will
     * be copied instead of the constant name. If you want to use the constant name, override the value:
     *
     * ```php
     * use Nette\PhpGenerator\Literal;
     *
     * $class = $this->>createClass('ClassName', MyBaseClass::class, [
     *     'constants' => [
     *         'MY_CONSTANT' => new Literal('self::FOO'),
     *     ],
     *     'properties' => [
     *         'myProperty' => new Literal('self::FOO'),
     *     ],
     *     'methods' => [
     *         'myMethod',
     *     ],
     * ]);
     *
     * foreach ($class->getMethod('myMethod')->getParameters() as $parameter) {
     *     if ($parameter->getName() === 'myParameter') {
     *         $parameter->setDefaultValue(new Literal('self::FOO'));
     *         break;
     *     }
     * }
     * ```
     *
     * @return ClassType
     */
    protected function createClass(
        ?string $className = null,
        ?string $baseClass = null,
        array $members = [],
    ): ClassType {
        $class = new ClassType($className);

        if ($baseClass) {
            $class->setExtends($baseClass);

            if (isset($members[self::CLASS_CONSTANTS])) {
                foreach ($members[self::CLASS_CONSTANTS] as $constantName => $constantValue) {
                    if (is_string($constantName)) {
                        $setValue = true;
                    } else {
                        $constantName = $constantValue;
                        $setValue = false;
                    }
                    $constantRef = new ReflectionClassConstant($baseClass, $constantName);
                    $constant = (new Factory())->fromConstantReflection($constantRef);
                    $constant->setComment($this->docBlock($constantRef));
                    if ($setValue) {
                        $constant->setValue($constantValue);
                    }
                    $class->addMember($constant);
                }
            }

            if (isset($members[self::CLASS_PROPERTIES])) {
                foreach ($members[self::CLASS_PROPERTIES] as $propertyName => $propertyValue) {
                    if (is_string($propertyName)) {
                        $setValue = true;
                    } else {
                        $propertyName = $propertyValue;
                        $setValue = false;
                    }
                    $propertyRef = new ReflectionProperty($baseClass, $propertyName);
                    $property = (new Factory())->fromPropertyReflection($propertyRef);
                    $property->setComment($this->docBlock($propertyRef));
                    if ($setValue) {
                        $property->setValue($propertyValue);
                    }
                    $class->addMember($property);
                }
            }

            if (isset($members[self::CLASS_METHODS])) {
                foreach ($members[self::CLASS_METHODS] as $methodName => $methodBody) {
                    if (is_string($methodName)) {
                        $setBody = true;
                    } else {
                        $methodName = $methodBody;
                        $setBody = false;
                    }
                    $methodRef = new ReflectionMethod($baseClass, $methodName);
                    $method = (new Factory())->fromMethodReflection($methodRef);
                    $method->setComment($this->docBlock($methodRef));
                    if ($setBody) {
                        $method->setBody($methodBody);
                    }
                    $class->addMember($method);
                }
            }
        }

        return $class;
    }

    private function docBlock(ReflectionClassConstant|ReflectionProperty|ReflectionMethod $member): ?string
    {
        if (!$this->controller->withDocblocks) {
            return null;
        }

        // Find the comment
        $comment = $member->getDocComment();
        if ($comment === false) {
            // Find the parent member that actually defines a comment, if any
            $member = $this->parentMemberWithComment($member, $comment);
            if (!$member) {
                return null;
            }
        }

        // Clean it up
        // (copied from @internal Nette\PhpGenerator\Helpers::unformatDocComment())
        $docBlock = preg_replace('#^\s*\* ?#m', '', trim(trim(trim($comment), '/*')));

        // Parse any @inheritdoc tags
        $docBlock = preg_replace_callback('/\{?@inheritdoc\}?/i', function(array $match) use ($member): string {
            $parentMember = $this->parentMemberWithComment($member);
            return ($parentMember ? $this->docBlock($parentMember) : null) ?? $match[1];
        }, $docBlock);

        return $docBlock;
    }

    private function parentMemberWithComment(
        ReflectionClassConstant|ReflectionProperty|ReflectionMethod $member,
        string|false &$comment = false,
    ): ReflectionClassConstant|ReflectionProperty|ReflectionMethod|null {
        foreach ($this->parentMembers($member) as $parentMember) {
            /** @var ReflectionClassConstant|ReflectionProperty|ReflectionMethod $parentMember */
            $comment = $parentMember->getDocComment();
            if ($comment !== false) {
                return $parentMember;
            }
        }
        return null;
    }

    private function parentMembers(
        ReflectionClassConstant|ReflectionProperty|ReflectionMethod $member,
    ): Generator {
        // Return each of the parents that have the same member
        while (true) {
            $parentClass = $member->getDeclaringClass()->getParentClass();
            if (!$parentClass) {
                break;
            }
            try {
                /** @phpstan-ignore-next-line  */
                $parentMember = match (true) {
                    $member instanceof ReflectionClassConstant => $parentClass->getConstant($member->getName()),
                    $member instanceof ReflectionProperty => $parentClass->getProperty($member->getName()),
                    $member instanceof ReflectionMethod => $parentClass->getMethod($member->getName()),
                };
            } catch (ReflectionException) {
                break;
            }
            if ($parentMember->isPrivate()) {
                break;
            }
            yield $parentMember;
            $member = $parentMember;
        }

        if (!$member->getDeclaringClass()->isInterface()) {
            // Then each of the interfaces implemented by the root declaring class
            foreach ($member->getDeclaringClass()->getInterfaces() as $interface) {
                try {
                    /** @phpstan-ignore-next-line  */
                    $interfaceMember = match (true) {
                        $member instanceof ReflectionClassConstant => $interface->getConstant($member->getName()),
                        $member instanceof ReflectionProperty => $interface->getProperty($member->getName()),
                        $member instanceof ReflectionMethod => $interface->getMethod($member->getName()),
                    };
                } catch (ReflectionException) {
                    continue;
                }
                yield $interfaceMember;
                yield from $this->parentMembers($interfaceMember);
            }
        }
    }

    /**
     * Writes out a PHP file using [[PsrPrinter]].
     *
     * @param string $file
     * @param PhpFile $phpFile
     */
    protected function writePhpFile(string $file, PhpFile $phpFile): void
    {
        $this->controller->writeToFile($file, (new PsrPrinter())->printFile($phpFile));
    }

    /**
     * Writes out a PHP class from a given namespace using [[PsrPrinter]].
     *
     * @param PhpNamespace $namespace The namespace populated with at least one class.
     */
    protected function writePhpClass(PhpNamespace $namespace): void
    {
        $classes = $namespace->getClasses();

        if (empty($classes)) {
            throw new InvalidArgumentException('The namespace doesn’t have any classes defined.');
        }

        $class = reset($classes);
        $dir = $this->namespaceDir($namespace);
        $path = sprintf('%s/%s.php', $dir, $class->getName());

        $file = new PhpFile();
        $file->addNamespace($namespace);
        $this->writePhpFile($path, $file);
    }

    private function namespaceDir(PhpNamespace $namespace): string
    {
        $ns = $namespace->getName();

        foreach (Composer::autoloadConfigFromFile($this->composerFile) as $rootNamespace => $rootPath) {
            if (str_starts_with("$ns\\", $rootNamespace)) {
                $rootDir = FileHelper::absolutePath($rootPath, dirname($this->composerFile), '/');
                return FileHelper::absolutePath(substr($ns, strlen($rootNamespace)), $rootDir, '/');
            }
        }

        throw new InvalidArgumentException("The namespace `$ns` isn’t autoloadable from `$this->composerFile`.");
    }
}
