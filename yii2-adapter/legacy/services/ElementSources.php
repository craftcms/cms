<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\events\DefineSourceSortOptionsEvent;
use craft\events\DefineSourceTableAttributesEvent;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Events\ElementSourceSortOptionsResolving;
use CraftCms\Cms\Element\Events\ElementSourceTableAttributesResolving;
use CraftCms\Cms\FieldLayout\FieldLayout;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use yii\base\Component;

/**
 * The Element Sources service provides APIs for managing element indexes.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getElementSources()|`Craft::$app->getElementSources()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementSources} instead.
 */
class ElementSources extends Component
{
    /**
     * @event DefineSourceTableAttributesEvent The event that is triggered when defining the available table attributes for a source.
     */
    public const EVENT_DEFINE_SOURCE_TABLE_ATTRIBUTES = 'defineSourceTableAttributes';

    /**
     * @event DefineSourceSortOptionsEvent The event that is triggered when defining the available sort options for a source.
     */
    public const EVENT_DEFINE_SOURCE_SORT_OPTIONS = 'defineSourceSortOptions';

    public const TYPE_HEADING = \CraftCms\Cms\Element\ElementSources::TYPE_HEADING;
    public const TYPE_NATIVE = \CraftCms\Cms\Element\ElementSources::TYPE_NATIVE;
    public const TYPE_CUSTOM = \CraftCms\Cms\Element\ElementSources::TYPE_CUSTOM;

    public const CONTEXT_FIELD = \CraftCms\Cms\Element\ElementSources::CONTEXT_FIELD;
    public const CONTEXT_INDEX = \CraftCms\Cms\Element\ElementSources::CONTEXT_INDEX;
    public const CONTEXT_MODAL = \CraftCms\Cms\Element\ElementSources::CONTEXT_MODAL;
    public const CONTEXT_SETTINGS = \CraftCms\Cms\Element\ElementSources::CONTEXT_SETTINGS;

    /**
     * Filters out any unnecessary headings from a given source list.
     *
     * @param array[]|Collection<array> $sources
     * @return array[]
     */
    public static function filterExtraHeadings(array|Collection $sources): array
    {
        return \CraftCms\Cms\Element\ElementSources::filterExtraHeadings($sources)->all();
    }

    /**
     * Returns the element index sources in the custom groupings/order.
     *
     * @param class-string<ElementInterface> $elementType The element type class
     * @param string $context The context
     * @param bool $withDisabled Whether disabled sources should be included
     * @param string|null $page The page to fetch sources for
     * @return array[]
     */
    public function getSources(
        string $elementType,
        string $context = self::CONTEXT_INDEX,
        bool $withDisabled = false,
        ?string $page = null,
    ): array {
        return app(\CraftCms\Cms\Element\ElementSources::class)->getSources($elementType, $context, $withDisabled, $page)->all();
    }

    /**
     * Returns whether the given source exists.
     *
     * @param class-string<ElementInterface> $elementType The element type class
     * @param string $sourceKey The source key
     * @param string $context The context
     * @param bool $withDisabled Whether disabled sources should be included
     * @param string|null $page The page to fetch sources for
     * @return bool
     * @since 5.7.11
     */
    public function sourceExists(
        string $elementType,
        string $sourceKey,
        string $context = self::CONTEXT_INDEX,
        bool $withDisabled = false,
        ?string $page = null,
    ): bool {
        return app(\CraftCms\Cms\Element\ElementSources::class)->sourceExists($elementType, $sourceKey, $context, $withDisabled, $page);
    }

    /**
     * Returns the unique pages found for the given element type’s sources.
     *
     * @param class-string<ElementInterface> $elementType The element type class
     * @param string $context The context
     * @param bool $withDisabled Whether disabled sources should be included
     * @return string[]
     * @since 5.9.0
     */
    public function getPages(string $elementType, string $context = self::CONTEXT_INDEX, bool $withDisabled = false): array
    {
        return app(\CraftCms\Cms\Element\ElementSources::class)->getPages($elementType)->all();
    }

    /**
     * Returns the first page found for the given element type’s sources.
     *
     * @param class-string<ElementInterface> $elementType The element type class
     * @param string $context The context
     * @param bool $withDisabled Whether disabled sources should be included
     * @return string|null
     * @since 5.9.0
     */
    public function getFirstPage(string $elementType, string $context = self::CONTEXT_INDEX, bool $withDisabled = false): ?string
    {
        return app(\CraftCms\Cms\Element\ElementSources::class)->getFirstPage($elementType, $context, $withDisabled);
    }

    /**
     * Returns whether the given page exists for an element type.
     *
     * @param class-string<ElementInterface> $elementType The element type class
     * @param string $context The context
     * @param bool $withDisabled Whether disabled sources should be included
     * @return bool
     * @since 5.9.0
     */
    public function pageExists(string $elementType, string $page, string $context = self::CONTEXT_INDEX, bool $withDisabled = false): bool
    {
        return app(\CraftCms\Cms\Element\ElementSources::class)->pageExists($elementType, $page, $context, $withDisabled);
    }

    /**
     * Returns a normalized ID for a given page name.
     *
     * @param string $page
     * @return string
     * @since 5.9.0
     */
    public function pageNameId(string $page): string
    {
        return app(\CraftCms\Cms\Element\ElementSources::class)->pageNameId($page);
    }

    /**
     * Returns the common table attributes that are available for a given element type, across all its sources.
     *
     * @param class-string<ElementInterface> $elementType The element type class
     * @return array[]
     */
    public function getAvailableTableAttributes(string $elementType): array
    {
        return app(\CraftCms\Cms\Element\ElementSources::class)->getAvailableTableAttributes($elementType)->all();
    }

    /**
     * Returns the attributes that should be shown for a given element type source.
     *
     * @param class-string<ElementInterface> $elementType The element type class
     * @param string $sourceKey The element type source key
     * @param string[]|null $customAttributes Custom attributes to show rather than the defaults
     * @param FieldLayout[]|null $fieldLayouts The field layouts that should be factored in
     * @return array[]
     */
    public function getTableAttributes(string $elementType, string $sourceKey, ?array $customAttributes = null, ?array $fieldLayouts = null): array
    {
        return app(\CraftCms\Cms\Element\ElementSources::class)->getTableAttributes($elementType, $sourceKey, $customAttributes, $fieldLayouts)->all();
    }

    /**
     * Returns all the field layouts available for the given element source.
     *
     * @param class-string<ElementInterface> $elementType
     * @param string $sourceKey
     *
     * @return \CraftCms\Cms\FieldLayout\FieldLayout[]
     */
    public function getFieldLayoutsForSource(string $elementType, string $sourceKey): array
    {
        return app(\CraftCms\Cms\Element\ElementSources::class)->getFieldLayoutsForSource($elementType, $sourceKey)->all();
    }

    /**
     * Returns additional sort options that should be available for a given element source.
     *
     * @param class-string<ElementInterface> $elementType The element type class
     * @param string $sourceKey The element source key
     * @return array[]
     */
    public function getSourceSortOptions(string $elementType, string $sourceKey): array
    {
        return app(\CraftCms\Cms\Element\ElementSources::class)->getSourceSortOptions($elementType, $sourceKey)->all();
    }

    /**
     * Returns additional sort options that should be available for an element index source that includes the given
     * field layouts.
     *
     * @param FieldLayout[]|Collection<FieldLayout> $fieldLayouts
     * @return array[]
     * @since 5.0.0
     */
    public function getSortOptionsForFieldLayouts(array|Collection $fieldLayouts): array
    {
        return app(\CraftCms\Cms\Element\ElementSources::class)->getSortOptionsForFieldLayouts($fieldLayouts)->all();
    }

    /**
     * Returns any table attributes that should be available for a given source, in addition to the [[getAvailableTableAttributes()|common attributes]].
     *
     * @param class-string<ElementInterface> $elementType The element type class
     * @param string $sourceKey The element source key
     * @return array[]
     */
    public function getSourceTableAttributes(string $elementType, string $sourceKey): array
    {
        return app(\CraftCms\Cms\Element\ElementSources::class)->getSourceTableAttributes($elementType, $sourceKey)->all();
    }

    /**
     * Returns any table attributes that should be available for an element index source that includes the given
     * field layouts.
     *
     * @param FieldLayout[] $fieldLayouts
     * @return array[]
     * @since 5.0.0
     */
    public function getTableAttributesForFieldLayouts(array $fieldLayouts): array
    {
        return app(\CraftCms\Cms\Element\ElementSources::class)->getTableAttributesForFieldLayouts($fieldLayouts)->all();
    }

    /**
     * Returns the page settings for a given element type.
     *
     * @param class-string<ElementInterface> $elementType
     * @return array
     * @since 5.9.0
     */
    public function getPageSettings(string $elementType): array
    {
        return app(\CraftCms\Cms\Element\ElementSources::class)->getPageSettings($elementType);
    }

    public static function registerEvents(): void
    {
        Event::listen(ElementSourceTableAttributesResolving::class, function(ElementSourceTableAttributesResolving $event) {
            // Fire a 'defineSourceTableAttributes' event
            if (Craft::$app->getElementSources()->hasEventHandlers(self::EVENT_DEFINE_SOURCE_TABLE_ATTRIBUTES)) {
                $yiiEvent = new DefineSourceTableAttributesEvent([
                    'elementType' => $event->elementType,
                    'source' => $event->source,
                    'attributes' => $event->attributes->all(),
                ]);
                Craft::$app->getElementSources()->trigger(self::EVENT_DEFINE_SOURCE_TABLE_ATTRIBUTES, $yiiEvent);
                $event->attributes = collect($yiiEvent->attributes);
            }
        });

        Event::listen(ElementSourceSortOptionsResolving::class, function(ElementSourceSortOptionsResolving $event) {
            // Fire a 'defineSourceTableAttributes' event
            if (Craft::$app->getElementSources()->hasEventHandlers(self::EVENT_DEFINE_SOURCE_SORT_OPTIONS)) {
                $yiiEvent = new DefineSourceSortOptionsEvent([
                    'elementType' => $event->elementType,
                    'source' => $event->source,
                    'sortOptions' => $event->sortOptions->all(),
                ]);
                Craft::$app->getElementSources()->trigger(self::EVENT_DEFINE_SOURCE_SORT_OPTIONS, $yiiEvent);
                $event->sortOptions = collect($yiiEvent->sortOptions);
            }
        });
    }
}
