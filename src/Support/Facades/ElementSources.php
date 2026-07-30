<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \Illuminate\Support\Collection<array-key, mixed> filterExtraHeadings(array<array-key, mixed>[]|\Illuminate\Support\Collection<array-key, mixed> $sources)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getSources(string $elementType, string $context = 'index', bool $withDisabled = false, string|null $page = null)
 * @method static bool sourceExists(string $elementType, string $sourceKey, string $context = 'index', bool $withDisabled = false, string|null $page = null)
 * @method static array<array-key, mixed>|null findSource(string $elementType, string $sourceKey, string $context = 'index', bool $withDisabled = false, string|null $page = null)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getPages(string $elementType)
 * @method static string|null getFirstPage(string $elementType, string $context = 'index', bool $withDisabled = false)
 * @method static bool pageExists(string $elementType, string $page, string $context = 'index', bool $withDisabled = false)
 * @method static string pageNameId(string $page)
 * @method static void saveSources(string $elementType, array<array-key, mixed> $sources)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getAvailableTableAttributes(string $elementType)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getTableAttributes(string $elementType, string $sourceKey, string[]|null $customAttributes = null, \CraftCms\Cms\FieldLayout\FieldLayout[]|null $fieldLayouts = null)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getFieldLayoutsForSource(string $elementType, string $sourceKey)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getSourceSortOptions(string $elementType, string $sourceKey)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getSortOptionsForFieldLayouts(\CraftCms\Cms\FieldLayout\FieldLayout[]|\Illuminate\Support\Collection<array-key, mixed> $fieldLayouts)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getSourceTableAttributes(string $elementType, string $sourceKey)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getTableAttributesForFieldLayouts(\CraftCms\Cms\FieldLayout\FieldLayout[]|\Illuminate\Support\Collection<array-key, mixed> $fieldLayouts)
 * @method static array<array-key, mixed> getPageSettings(string $elementType)
 * @method static void savePageSettings(string $elementType, array<array-key, mixed> $pageSettings)
 *
 * @see \CraftCms\Cms\Element\ElementSources
 */
class ElementSources extends Facade
{
    public const string TYPE_HEADING = \CraftCms\Cms\Element\ElementSources::TYPE_HEADING;

    public const string TYPE_NATIVE = \CraftCms\Cms\Element\ElementSources::TYPE_NATIVE;

    public const string TYPE_CUSTOM = \CraftCms\Cms\Element\ElementSources::TYPE_CUSTOM;

    public const string CONTEXT_FIELD = \CraftCms\Cms\Element\ElementSources::CONTEXT_FIELD;

    public const string CONTEXT_INDEX = \CraftCms\Cms\Element\ElementSources::CONTEXT_INDEX;

    public const string CONTEXT_MODAL = \CraftCms\Cms\Element\ElementSources::CONTEXT_MODAL;

    public const string CONTEXT_SETTINGS = \CraftCms\Cms\Element\ElementSources::CONTEXT_SETTINGS;

    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Element\ElementSources::class;
    }
}
