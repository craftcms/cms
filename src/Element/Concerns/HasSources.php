<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Events\ElementFieldLayoutsResolving;
use CraftCms\Cms\Element\Events\ElementSourcesResolving;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Typecast;

/**
 * HasSources provides element source management functionality.
 *
 * This trait contains methods for defining, finding, and working with element sources,
 * as well as managing field layouts associated with sources.
 *
 * @internal
 */
trait HasSources
{
    /**
     * @var array<class-string,array<string,array<array-key,mixed>>>
     *
     * @see sources()
     */
    private static array $sources = [];

    public static function multiPageSources(): bool
    {
        return false;
    }

    /**
     * The control panel index URI a source has of its own, if it has one.
     *
     * A section or a volume is reachable at a URL that names it —
     * `content/entries/blog`, `assets/uploads` — rather than only through a
     * `?source=` query on the type's index. The nav and the breadcrumbs both
     * need that URL, so it's answered here rather than being rebuilt by each
     * caller from the source array's innards.
     *
     * Returns `null` for a source with no page of its own (a custom source,
     * say), leaving the caller to fall back to the query form.
     *
     * @param  array<string, mixed>  $source  A row from {@see sources()}
     * @param  string|null  $page  The index page the source belongs to
     */
    public static function sourceCpUri(array $source, ?string $page = null): ?string
    {
        return null;
    }

    public static function sources(string $context): array
    {
        if (! isset(self::$sources[static::class][$context])) {
            // Memoize the results immediately, in case sources() gets called again via the event
            self::$sources[static::class][$context] = static::defineSources($context);

            event($event = new ElementSourcesResolving(static::class, $context, self::$sources[static::class][$context]));
            self::$sources[static::class][$context] = $event->sources;
        }

        return self::$sources[static::class][$context];
    }

    /**
     * Defines the sources that elements of this type may belong to.
     *
     * @param  string  $context  The context ('index', 'modal', 'field', or 'settings').
     * @return array<array-key,mixed> The sources.
     *
     * @see sources()
     */
    protected static function defineSources(string $context): array
    {
        return [];
    }

    public static function findSource(string $sourceKey, ?string $context): ?array
    {
        return null;
    }

    public static function sourcePath(string $sourceKey, string $stepKey, ?string $context): ?array
    {
        return null;
    }

    public static function modifyCustomSource(array $config): array
    {
        return $config;
    }

    public static function supportsSourceBadgeCounts(): bool
    {
        return false;
    }

    public static function sourceSupportsBadgeCount(array $source): bool
    {
        if (! static::supportsSourceBadgeCounts()) {
            return false;
        }

        $isHeading = isset($source['type'])
            ? $source['type'] === ElementSources::TYPE_HEADING
            : isset($source['heading']);

        if ($isHeading) {
            return false;
        }

        return ($source['key'] ?? '') !== '';
    }

    /**
     * Counts every element the source lists for the current user, whatever its
     * status, leaving out drafts, revisions and trashed elements.
     *
     * Criteria and conditions that depend on who's looking change the query,
     * and with it the cache key, so each permission set gets its own count.
     */
    public static function sourceBadgeCount(array $source, ?int $siteId): int
    {
        $query = static::find()->status(null);

        if ($siteId !== null) {
            $query->siteId($siteId);
        }

        if (($source['type'] ?? null) === ElementSources::TYPE_CUSTOM) {
            if (! empty($source['condition'])) {
                /** @var ElementConditionInterface $condition */
                $condition = Conditions::createCondition($source['condition']);
                $condition->forQuery = true;
                $condition->modifyQuery($query);
            }
        } elseif (! empty($source['criteria'])) {
            Typecast::configure($query, ElementHelper::cleanseQueryCriteria($source['criteria']));
        }

        return $query->cache()->count();
    }

    public static function fieldLayouts(?string $source): array
    {
        $fieldLayouts = static::defineFieldLayouts($source);

        event($event = new ElementFieldLayoutsResolving(static::class, $source, $fieldLayouts));

        return $event->fieldLayouts;
    }

    /**
     * Defines the field layouts associated with elements for a given source.
     *
     * @param  string|null  $source  The selected source's key, or `null` if all known field layouts should be returned
     * @return FieldLayout[] The associated field layouts
     *
     * @see fieldLayouts()
     */
    protected static function defineFieldLayouts(?string $source): array
    {
        // Default to all the field layouts associated with this element type
        return Fields::getLayoutsByType(static::class)->all();
    }
}
