<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Element\ElementEagerLoader;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\ElementQuery;

abstract class TestElementEagerLoaderElement extends Element
{
    private static array $eagerLoadingCallsByClass = [];

    private static array $eagerLoadingMapsByClass = [];

    public static function resetTestState(): void
    {
        self::$eagerLoadingCallsByClass = [];
        self::$eagerLoadingMapsByClass = [];
    }

    public static function setTestEagerLoadingMap(string $handle, array|false|null|Closure $map): void
    {
        self::$eagerLoadingMapsByClass[static::class][$handle] = $map;
    }

    public static function eagerLoadingCalls(): array
    {
        return self::$eagerLoadingCallsByClass[static::class] ?? [];
    }

    #[\Override]
    public static function find(): ElementQuery
    {
        return new TestElementEagerLoaderQuery(static::class);
    }

    #[\Override]
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|false|null
    {
        self::$eagerLoadingCallsByClass[static::class][] = [
            'handle' => $handle,
            'ids' => array_map(fn (ElementInterface $element) => $element->id, $sourceElements),
            'siteIds' => array_map(fn (ElementInterface $element) => $element->siteId, $sourceElements),
        ];

        $map = self::$eagerLoadingMapsByClass[static::class][$handle] ?? null;

        if ($map instanceof Closure) {
            return $map($sourceElements, $handle);
        }

        return $map;
    }
}
