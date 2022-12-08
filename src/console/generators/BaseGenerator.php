<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\generators;

use Craft;
use craft\base\PluginInterface;
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
use yii\base\Application;
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
    protected const CLASS_IMPLEMENTS = 'implements';
    protected const CLASS_CONSTANTS = 'constants';
    protected const CLASS_PROPERTIES = 'properties';
    protected const CLASS_METHODS = 'methods';

    protected const ID_PATTERN = '[a-z]([a-z0-9\\-]*[a-z0-9])?';

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
     * Returns the module’s file path.
     *
     * @return string
     */
    protected function moduleFile(): string
    {
        return (new ReflectionClass($this->module))->getFileName();
    }

    /**
     * Prompts the user for a PHP namespace.
     *
     * @param string $text The prompt text
     * @param array $options Prompt options:
     *
     * - `required` (bool): whether a value is required
     * - `ensureContained` (bool): whether the namespace must be contained within [[baseNamespace]].
     *   If this isn’t set, it will default to whether [[baseNamespace]] is set.
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

        if (!isset($options['ensureContained'])) {
            $options['ensureContained'] = isset($this->baseNamespace);
        } elseif ($options['ensureContained'] && !isset($this->baseNamespace)) {
            throw new NotSupportedException('`ensureContained` is only supported by `namespacePrompt()` if `baseNamespace` is set.');
        }

        if (isset($options['default'])) {
            $options['default'] = App::normalizeNamespace($options['default']);
            if ($options['ensureContained'] && !str_starts_with("{$options['default']}\\", "$this->baseNamespace\\")) {
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
                if ($options['ensureContained'] && !str_starts_with("$namespace\\", "$this->baseNamespace\\")) {
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
     * Prompts the user for an ID, such as a module ID or action name (kebab-case).
     *
     * @param string $text The prompt text
     * @param array $options Prompt options:
     *
     * - `required` (bool): whether a value is required
     * - `allowNesting' (bool): whether the ID can be nested (e.g. `foo/bar/my-id`)
     * - `default` (string): the default value to use if no input is given
     *
     * @return string
     */
    protected function idPrompt(string $text, array $options = []): string
    {
        if (isset($options['pattern'])) {
            throw new NotSupportedException('`pattern` is not supported by `idPrompt()`.');
        }

        if (isset($options['validator'])) {
            throw new NotSupportedException('`validator` is not supported by `idPrompt()`.');
        }

        return $this->controller->prompt($this->controller->markdownToAnsi("$text (kebab-case)"), [
            'pattern' => sprintf('/^%s%s$/', !empty($options['allowNesting']) ? '([a-z][a-z0-9]*\\/)*' : '', self::ID_PATTERN),
        ] + $options);
    }

    /**
     * Prompts the user for a PHP class name.
     *
     * @param string $text The prompt text
     * @param array $options Prompt options:
     *
     * - `required` (bool): whether a value is required
     * - `default` (string): the default value to use if no input is given
     *
     * @return string
     */
    protected function classNamePrompt(string $text, array $options = []): string
    {
        if (isset($options['pattern'])) {
            throw new NotSupportedException('`pattern` is not supported by `classNamePrompt()`.');
        }

        if (isset($options['validator'])) {
            throw new NotSupportedException('`validator` is not supported by `classNamePrompt()`.');
        }

        return $this->controller->prompt($this->controller->markdownToAnsi("$text (PascalCase)"), [
            'pattern' => '/^[a-z]\w*$/i',
        ] + $options);
    }

    /**
     * Prompts the user for a fully-qualified PHP class.
     *
     * @param string $text The prompt text
     * @param array $options Prompt options:
     *
     * - `required` (bool): whether a value is required
     * - `ensureExists` (bool): whether the class must exist
     * - `default` (string): the default value to use if no input is given
     * - `validator` (callable): a callable function to validate input. The function must accept two parameters:
     *     - `$class`: the normalized class name
     *     - `$error`: passed by reference, to be set to the error text if validation failed
     *
     * @return string|null the normalized class
     */
    protected function classPrompt(string $text, array $options = []): ?string
    {
        if (isset($options['pattern'])) {
            throw new NotSupportedException('`pattern` is not supported by `classPrompt()`.');
        }

        $class = $this->controller->prompt($this->controller->markdownToAnsi($text), [
            'validator' => function(string $input, ?string &$error) use ($options): bool {
                try {
                    $class = App::normalizeNamespace($input);
                } catch (InvalidArgumentException) {
                    $error = 'Invalid class';
                    return false;
                }
                if ($options['ensureExists'] && !class_exists($class)) {
                    $error = $this->controller->markdownToAnsi("`$class` does not exist.");
                    return false;
                }
                if (isset($options['validator'])) {
                    return $options['validator']($class, $error);
                }
                return true;
            },
        ] + $options);

        if (!$class) {
            return null;
        }

        return App::normalizeNamespace($class);
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
            'ensureContained' => isset($this->baseNamespace),
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
     * @param array $options Options for the generated class:
     *
     * - `implements`: Array of interfaces that the class should implement directly.
     * - `constants`: Array of constant names that should be copied from the base class or interfaces.
     *    You can use key/value pairs to override default values.
     * - `properties`: Array of property names that should be copied from the base class or interfaces.
     *    You can use key/value pairs to override default values.
     * - `methods`: Array of method names that should be copied from the base class or interfaces.
     *    You can use key/value pairs to override the method bodies.
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
        array $options = [],
    ): ClassType {
        $class = new ClassType($className);

        $subClasses = [];

        if ($baseClass) {
            $class->setExtends($baseClass);
            $subClasses[] = $baseClass;
        }

        if (!empty($options[self::CLASS_IMPLEMENTS])) {
            foreach ($options[self::CLASS_IMPLEMENTS] as $interface) {
                $class->addImplement($interface);
                $subClasses[] = $interface;
            }
        }

        if (isset($options[self::CLASS_CONSTANTS])) {
            foreach ($options[self::CLASS_CONSTANTS] as $constantName => $constantValue) {
                if (is_string($constantName)) {
                    $setValue = true;
                } else {
                    $constantName = $constantValue;
                    $setValue = false;
                }
                /** @var ReflectionClassConstant $constantRef */
                $constantRef = $this->findRef($subClasses, fn(string $subClass) => new ReflectionClassConstant($subClass, $constantName));
                $constant = (new Factory())->fromConstantReflection($constantRef);
                $constant->setComment($this->docBlock($constantRef));
                if ($setValue) {
                    $constant->setValue($constantValue);
                }
                $class->addMember($constant);
            }
        }

        if (isset($options[self::CLASS_PROPERTIES])) {
            foreach ($options[self::CLASS_PROPERTIES] as $propertyName => $propertyValue) {
                if (is_string($propertyName)) {
                    $setValue = true;
                } else {
                    $propertyName = $propertyValue;
                    $setValue = false;
                }
                /** @var ReflectionProperty $propertyRef */
                $propertyRef = $this->findRef($subClasses, fn(string $subClass) => new ReflectionProperty($subClass, $propertyName));
                $property = (new Factory())->fromPropertyReflection($propertyRef);
                $property->setComment($this->docBlock($propertyRef));
                if ($setValue) {
                    $property->setValue($propertyValue);
                }
                $class->addMember($property);
            }
        }

        if (isset($options[self::CLASS_METHODS])) {
            foreach ($options[self::CLASS_METHODS] as $methodName => $methodBody) {
                if (is_string($methodName)) {
                    $setBody = true;
                } else {
                    $methodName = $methodBody;
                    $setBody = false;
                }
                /** @var ReflectionMethod $methodRef */
                $methodRef = $this->findRef($subClasses, fn(string $subClass) => new ReflectionMethod($subClass, $methodName));
                $method = (new Factory())->fromMethodReflection($methodRef);
                $method->setAbstract(false);
                $method->setComment($this->docBlock($methodRef));
                if ($setBody) {
                    $method->setBody($methodBody);
                }
                $class->addMember($method);
            }
        }

        return $class;
    }

    private function findRef(
        array $subClasses,
        callable $createRef,
    ): ReflectionClassConstant|ReflectionProperty|ReflectionMethod {
        if (empty($subClasses)) {
            throw new InvalidArgumentException('Unable to find subclass members when no base class or interfaces were provided.');
        }

        foreach ($subClasses as $subClass) {
            try {
                return $createRef($subClass);
            } catch (ReflectionException $e) {
            }
        }

        throw $e;
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
        $basePath = $this->namespacePath($namespace->getName());
        $path = sprintf('%s/%s.php', $basePath, $class->getName());

        $file = new PhpFile();
        $file->addNamespace($namespace);
        $this->writePhpFile($path, $file);
    }

    /**
     * Returns the path that corresponds to a given namespace.
     *
     * @param string $namespace
     * @return string
     * @throws InvalidArgumentException if the namespace isn’t autoloadable from [[composerFile]].
     */
    protected function namespacePath(string $namespace): string
    {
        foreach (Composer::autoloadConfigFromFile($this->composerFile) as $rootNamespace => $rootPath) {
            if (str_starts_with("$namespace\\", $rootNamespace)) {
                $rootDir = FileHelper::absolutePath($rootPath, dirname($this->composerFile), '/');
                return FileHelper::absolutePath(substr($namespace, strlen($rootNamespace)), $rootDir, '/');
            }
        }

        throw new InvalidArgumentException("The namespace `$namespace` isn’t autoloadable from `$this->composerFile`.");
    }

    /**
     * Wraps a string in `Craft::t()` if the component is being generated for Craft or a plugin.
     *
     * @param string $message The string to output
     * @return string The PHP code
     */
    protected function messagePhp(string $message): string
    {
        $messagePhp = var_export($message, true);
        $category = match (true) {
            $this->module instanceof Application => 'app',
            $this->module instanceof PluginInterface => $this->module->id,
            default => null,
        };

        return $category ? sprintf("Craft::t('%s', %s)", $category, $messagePhp) : $messagePhp;
    }
}
