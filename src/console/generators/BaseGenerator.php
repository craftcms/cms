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
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Constant;
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

        $namespace = $this->controller->prompt($this->controller->markdownToAnsi($text), [
            'validator' => function(string $input, ?string &$error) use ($options): bool {
                try {
                    $namespace = App::normalizeNamespace($input);
                } catch (InvalidArgumentException) {
                    $error = 'Invalid namespace';
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
     * - `ensureCouldAutoload` (bool): whether the directory must be capable of being autoloaded from composer.json
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
            if (
                !empty($options['ensureCouldAutoload']) &&
                !Composer::couldAutoload($path, $this->composerFile, reason: $reason)
            ) {
                $error = $this->controller->markdownToAnsi($reason);
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
     * Ensures that a directory is autoloadable in composer.json,
     * and returns the root namespace for the directory.
     *
     * @param string $dir The directory path
     * @param bool $addedRoot Whether a new autoload root was added
     * @return string
     */
    protected function ensureAutoloadable(string $dir, ?bool &$addedRoot = false): string
    {
        $dir = FileHelper::absolutePath($dir, ds: '/');

        if (!Composer::couldAutoload($dir, $this->composerFile, $existingRoot, $reason)) {
            throw new InvalidArgumentException($reason);
        }

        if ($existingRoot) {
            [$rootNamespace, $rootPath] = $existingRoot;

            if ($dir === $rootPath) {
                return rtrim($rootNamespace, '\\');
            }

            $relativePath = FileHelper::relativePath($dir, $rootPath);
            return $rootNamespace . App::normalizeNamespace($relativePath);
        }

        $composerDir = dirname(FileHelper::absolutePath($this->composerFile, ds: '/'));
        $newRootPath = FileHelper::relativePath($dir, $composerDir) . '/';

        $newRootNamespace = $this->namespacePrompt("What should the root namespace for `$newRootPath` be?", [
            'required' => true,
        ]);

        $composerConfig = Json::decodeFromFile($this->composerFile);
        $composerConfig['autoload']['psr-4']["$newRootNamespace\\"] = $newRootPath;
        $this->controller->writeJson($this->composerFile, $composerConfig);

        $addedRoot = true;
        return $newRootNamespace;
    }

    /**
     * Creates a new [[ClassType]] that extends a given base class, and populates it with some of its
     * constants, properties, and methods.
     *
     * @param string|null $className The class name
     * @param PhpNamespace|null $namespace The namespace
     * @param string|null $baseClass The base class
     * @param array $members Class members that should be added:
     *
     * - `constants`: Array of constant names. You can use key/value pairs to override default values.
     * - `properties`: Array of property names. You can use key/value pairs to override default values.
     * - `methods`: Array of method names. You can use key/value pairs to override the method bodies.
     *
     *  If a base class is defined, their signatures and docblocks will be pulled in from there.
     *
     * @return ClassType
     */
    protected function createClass(
        ?string $className = null,
        ?PhpNamespace $namespace = null,
        ?string $baseClass = null,
        array $members = [],
    ): ClassType {
        $class = new ClassType($className, $namespace);

        if ($baseClass) {
            $class->setExtends($baseClass);
        }

        if (isset($members[self::CLASS_CONSTANTS])) {
            foreach ($members[self::CLASS_CONSTANTS] as $constantName => $constantValue) {
                if (is_string($constantName)) {
                    $setValue = true;
                } else {
                    $constantName = $constantValue;
                    $setValue = false;
                }
                if ($baseClass) {
                    $constantRef = new ReflectionClassConstant($baseClass, $constantName);
                    $constant = (new Factory())->fromConstantReflection($constantRef);
                    $constant->setComment($this->docBlock($constantRef));
                    if ($setValue) {
                        $constant->setValue($constantValue);
                    }
                    $class->addMember($constant);
                } else {
                    $class->addConstant($constantName, $setValue ? $constantValue : null);
                }
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
                if ($baseClass) {
                    $propertyRef = new ReflectionProperty($baseClass, $propertyName);
                    $property = (new Factory())->fromPropertyReflection($propertyRef);
                    $property->setComment($this->docBlock($propertyRef));
                    if ($setValue) {
                        $property->setValue($propertyValue);
                    }
                    $class->addMember($property);
                } elseif ($setValue) {
                    $class->addProperty($propertyName, $propertyValue);
                } else {
                    $class->addProperty($propertyName);
                }
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
                if ($baseClass) {
                    $methodRef = new ReflectionMethod($baseClass, $methodName);
                    $method = (new Factory())->fromMethodReflection($methodRef);
                    $method->setComment($this->docBlock($methodRef));
                    if ($setBody) {
                        $method->setBody($methodBody);
                    }
                    $class->addMember($method);
                } else {
                    $method = $class->addMethod($methodName);
                    if ($setBody) {
                        $method->setBody($methodBody);
                    }
                }
            }
        }

        if ($namespace) {
            $namespace->add($class);
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
    ) {
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
                foreach ($this->parentMembers($interfaceMember) as $parentMember) {
                    yield $parentMember;
                }
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
}
