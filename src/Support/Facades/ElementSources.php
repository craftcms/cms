<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \Illuminate\Support\Collection getSources(string $elementType, string $context = \CraftCms\Cms\Element\ElementSources::CONTEXT_INDEX, bool $withDisabled = false, ?string $page = null)
 * @method static array|null findSource(string $elementType, string $sourceKey, string $context = \CraftCms\Cms\Element\ElementSources::CONTEXT_INDEX, bool $withDisabled = false, ?string $page = null)
 * @method static bool sourceExists(string $elementType, string $sourceKey, string $context = \CraftCms\Cms\Element\ElementSources::CONTEXT_INDEX, bool $withDisabled = false, ?string $page = null)
 * @method static array getPages(string $elementType)
 * @method static \CraftCms\Cms\FieldLayout\FieldLayout|null getSourceFieldLayout(string $elementType, string $sourceKey, string $context = \CraftCms\Cms\Element\ElementSources::CONTEXT_INDEX, ?string $page = null)
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
