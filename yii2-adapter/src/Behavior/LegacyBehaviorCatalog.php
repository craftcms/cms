<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Behavior;

use craft\base\Model;
use CraftCms\Cms\Auth\Methods\BaseAuthMethod;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Condition\BaseCondition;
use CraftCms\Cms\Condition\BaseConditionRule;
use CraftCms\Cms\Config\BaseConfig;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Field\Data\ColorData;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Image\Image;
use CraftCms\Cms\ProjectConfig\Data\ReadOnlyProjectConfigData;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Data\SiteGroup;
use CraftCms\Cms\User\Data\UserGroup;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class LegacyBehaviorCatalog
{
    /**
     * @var list<string>
     */
    private const array DIRECTORIES = [
        'legacy/base',
        'legacy/elements',
        'legacy/fieldlayoutelements',
        'legacy/fields',
        'legacy/fs',
        'legacy/image',
        'legacy/imagetransforms',
        'legacy/models',
        'legacy/widgets',
    ];

    /**
     * @var list<class-string>
     */
    private const array ROOTS = [
        Component::class,
        BaseAuthMethod::class,
        BaseModel::class,
    ];

    /**
     * @var array<class-string, list<class-string>>
     */
    private const array BASE_REGISTRATIONS = [
        Component::class => [
            Model::class,
            \craft\base\Component::class,
        ],
        BaseAuthMethod::class => [
            Model::class,
            \craft\base\Component::class,
        ],
        BaseConfig::class => [
            Model::class,
        ],
        BaseModel::class => [
            Model::class,
        ],
        ReadOnlyProjectConfigData::class => [
            Model::class,
        ],
        ColorData::class => [
            Model::class,
        ],
    ];

    /**
     * @var array<class-string, list<class-string>>|null
     */
    private static ?array $registrations = null;

    /**
     * @var list<array{path: string, legacyClass: class-string, targetClass: class-string}>|null
     */
    private static ?array $discoveredTargets = null;

    /**
     * @return array<class-string, list<class-string>>
     */
    public static function registrations(): array
    {
        if (self::$registrations !== null) {
            return self::$registrations;
        }

        $registrations = self::BASE_REGISTRATIONS;

        foreach (self::discoveredTargets() as $target) {
            self::appendRegistration($registrations, $target['targetClass'], $target['legacyClass']);
        }

        return self::$registrations = $registrations;
    }

    /**
     * @return list<class-string>
     */
    public static function mixinTargets(): array
    {
        $targets = [
            BaseAuthMethod::class,
            BaseCondition::class,
            BaseConditionRule::class,
            BaseConfig::class,
            BaseModel::class,
            ColorData::class,
            EntryType::class,
            FieldLayout::class,
            Image::class,
            ReadOnlyProjectConfigData::class,
            Section::class,
            SectionSiteSettings::class,
            Site::class,
            SiteGroup::class,
            UserGroup::class,
        ];

        foreach (self::discoveredTargets() as $target) {
            $targets[] = $target['targetClass'];
        }

        $targets = array_values(array_filter(array_unique($targets), fn(string $class) => is_callable([$class, 'macro'])));

        sort($targets);

        return $targets;
    }

    /**
     * @return list<array{path: string, legacyClass: class-string, targetClass: class-string}>
     */
    public static function discoveredTargets(): array
    {
        if (self::$discoveredTargets !== null) {
            return self::$discoveredTargets;
        }

        $targets = [];

        foreach (self::files() as $path) {
            $target = self::discoverTarget($path);

            if ($target === null || !self::supportsBehaviorCompatibility($target['targetClass'])) {
                continue;
            }

            $targets[] = $target;
        }

        usort($targets, fn(array $a, array $b) => [$a['legacyClass'], $a['targetClass']] <=> [$b['legacyClass'], $b['targetClass']]);

        return self::$discoveredTargets = $targets;
    }

    /**
     * @return list<string>
     */
    private static function files(): array
    {
        $files = [];
        $root = dirname(__DIR__, 2);

        foreach (self::DIRECTORIES as $directory) {
            $path = $root . '/' . $directory;

            if (!is_dir($path)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /**
     * @return array{path: string, legacyClass: class-string, targetClass: class-string}|null
     */
    private static function discoverTarget(string $path): ?array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $definition = self::parseClassDefinition($contents);

        if ($definition === null) {
            return null;
        }

        $shortName = $definition['shortName'];
        $namespace = $definition['namespace'];

        $targetClass = null;

        if ($definition['extends'] !== null) {
            $extends = $definition['extends'];
            $extendsRoot = explode('\\', $extends, 2)[0];
            $extends = $definition['uses'][$extendsRoot] ?? $extends;

            if (str_starts_with($extends, 'CraftCms\\Cms\\')) {
                $targetClass = $extends;
            }
        }

        if ($targetClass === null) {
            return null;
        }

        return [
            'path' => $path,
            'legacyClass' => ($namespace !== null ? $namespace . '\\' : '') . $shortName,
            'targetClass' => $targetClass,
        ];
    }

    /**
     * @return array{namespace: string|null, shortName: string, extends: string|null, uses: array<string, class-string>}|null
     */
    private static function parseClassDefinition(string $contents): ?array
    {
        $tokens = token_get_all($contents);
        $namespace = null;
        $shortName = null;
        $extends = null;
        $uses = [];
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = self::collectQualifiedName($tokens, $i + 1);
                continue;
            }

            if ($token[0] === T_USE) {
                $use = self::collectUseAlias($tokens, $i + 1);

                if ($use !== null) {
                    $uses[$use['alias']] = $use['class'];
                }

                continue;
            }

            if ($token[0] !== T_CLASS) {
                continue;
            }

            for ($j = $i + 1; $j < $count; $j++) {
                $next = $tokens[$j];

                if (!is_array($next)) {
                    continue;
                }

                if ($next[0] === T_STRING) {
                    $shortName = $next[1];
                    break;
                }
            }

            if ($shortName === null) {
                return null;
            }

            for ($j = $j + 1; $j < $count; $j++) {
                $next = $tokens[$j];

                if (is_string($next) && $next === '{') {
                    break;
                }

                if (!is_array($next) || $next[0] !== T_EXTENDS) {
                    continue;
                }

                $extends = self::collectQualifiedName($tokens, $j + 1);
                break;
            }

            return [
                'namespace' => $namespace,
                'shortName' => $shortName,
                'extends' => $extends !== null ? ltrim($extends, '\\') : null,
                'uses' => $uses,
            ];
        }

        return null;
    }

    /**
     * @param  array<int, array{int, string, int}|string>  $tokens
     * @return array{class: class-string, alias: string}|null
     */
    private static function collectUseAlias(array $tokens, int $offset): ?array
    {
        $class = self::collectQualifiedName($tokens, $offset);

        if ($class === null) {
            return null;
        }

        $alias = class_basename($class);

        for ($i = $offset, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_string($token) && $token === ';') {
                break;
            }

            if (!is_array($token) || $token[0] !== T_AS) {
                continue;
            }

            $alias = self::collectQualifiedName($tokens, $i + 1) ?? $alias;
            break;
        }

        return [
            'class' => ltrim($class, '\\'),
            'alias' => $alias,
        ];
    }

    /**
     * @param  array<int, array{int, string, int}|string>  $tokens
     */
    private static function collectQualifiedName(array $tokens, int $offset): ?string
    {
        $parts = [];

        for ($i = $offset, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_string($token)) {
                if ($token === ';' || $token === '{' || $token === ',') {
                    break;
                }

                if ($token === '\\') {
                    $parts[] = '\\';
                }

                continue;
            }

            if (in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                $parts[] = $token[1];

                continue;
            }

            if ($token[0] === T_WHITESPACE) {
                continue;
            }

            break;
        }

        if ($parts === []) {
            return null;
        }

        return implode('', $parts);
    }

    private static function supportsBehaviorCompatibility(string $class): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        foreach (self::ROOTS as $root) {
            if ($class === $root || is_subclass_of($class, $root)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<class-string, list<class-string>>  $registrations
     * @param  class-string  $class
     * @param  class-string  $legacyClass
     */
    private static function appendRegistration(array &$registrations, string $class, string $legacyClass): void
    {
        $registrations[$class] ??= [];

        if (in_array($legacyClass, $registrations[$class], true)) {
            return;
        }

        $registrations[$class][] = $legacyClass;
    }
}
