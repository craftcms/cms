<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \Illuminate\Support\Collection<array> filterExtraHeadings(array[]|\Illuminate\Support\Collection<array> $sources)
 * @method static \Illuminate\Support\Collection<array> getSources(class-string<\craft\base\ElementInterface> $elementType, string $context = 'index', bool $withDisabled = false, string|null $page = null)
 * @method static bool sourceExists(class-string<\craft\base\ElementInterface> $elementType, string $sourceKey, string $context = 'index', bool $withDisabled = false, string|null $page = null)
 * @method static array|null findSource(class-string<\craft\base\ElementInterface> $elementType, string $sourceKey, string $context = 'index', bool $withDisabled = false, string|null $page = null)
 * @method static \Illuminate\Support\Collection<string> getPages(class-string<\craft\base\ElementInterface> $elementType)
 * @method static string|null getFirstPage(class-string<\craft\base\ElementInterface> $elementType, string $context = 'index', bool $withDisabled = false)
 * @method static bool pageExists(class-string<\craft\base\ElementInterface> $elementType, string $page, string $context = 'index', bool $withDisabled = false)
 * @method static string pageNameId(string $page)
 * @method static void saveSources(class-string<\craft\base\ElementInterface> $elementType, array $sources)
 * @method static \Illuminate\Support\Collection<array> getAvailableTableAttributes(class-string<\craft\base\ElementInterface> $elementType)
 * @method static \Illuminate\Support\Collection<array> getTableAttributes(class-string<\craft\base\ElementInterface> $elementType, string $sourceKey, string[]|null $customAttributes = null)
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\FieldLayout\FieldLayout> getFieldLayoutsForSource(class-string<\craft\base\ElementInterface> $elementType, string $sourceKey)
 * @method static \Illuminate\Support\Collection<array> getSourceSortOptions(class-string<\craft\base\ElementInterface> $elementType, string $sourceKey)
 * @method static \Illuminate\Support\Collection<array> getSortOptionsForFieldLayouts(\CraftCms\Cms\FieldLayout\FieldLayout[]|\Illuminate\Support\Collection<\CraftCms\Cms\FieldLayout\FieldLayout> $fieldLayouts)
 * @method static \Illuminate\Support\Collection<array> getSourceTableAttributes(class-string<\craft\base\ElementInterface> $elementType, string $sourceKey)
 * @method static \Illuminate\Support\Collection<array> getTableAttributesForFieldLayouts(\CraftCms\Cms\FieldLayout\FieldLayout[]|\Illuminate\Support\Collection<\CraftCms\Cms\FieldLayout\FieldLayout> $fieldLayouts)
 * @method static array getPageSettings(class-string<\craft\base\ElementInterface> $elementType)
 * @method static void savePageSettings(class-string<\craft\base\ElementInterface> $elementType, array $pageSettings)
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
