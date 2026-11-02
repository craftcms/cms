<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Attributes;

use Attribute;
use Closure;
use CraftCms\Cms\Element\Attributes\ElementProperty;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Attributes\ElementQueryParam;
use CraftCms\Cms\Gql\GqlHelper;

/**
 * Controls how GraphQL exposes an element query param or element property.
 *
 * Params declared with {@see ElementQueryParam} become query arguments, readable properties declared with
 * {@see ElementProperty} become element interface fields, and writable ones become mutation arguments, with the type and
 * description from that attribute. Place this attribute alongside it to gate or exclude them:
 *
 * ```php
 * #[Gql(exclude: true)]
 * ```
 *
 * Params and properties that require an element capability, such as structures, only apply to element types that have
 * it, so they don’t need a gate for that. A gate adds GraphQL-specific conditions, such as schema permissions. It decides
 * which element types and schemas get a query argument or field. It’s a static closure that receives
 * the element type and returns whether to include it:
 *
 * ```php
 * #[Gql(static function (string $elementType): bool {
 *     return GqlHelper::canSchema('myplugin.inventory');
 * })]
 * ```
 *
 * @since 6.0.0
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
class Gql
{
    /**
     * Includes it for element types that have URIs. Assets define their own `url` field, so the element `url` field is
     * gated by this instead of by an element capability.
     */
    public const Closure Uris = static function (string $elementType): bool {
        return $elementType::hasUris();
    };

    /** Includes it when the schema can query drafts. */
    public const Closure CanQueryDrafts = static function (string $elementType): bool {
        return GqlHelper::canQueryDrafts();
    };

    /** Includes it when the schema can query revisions. */
    public const Closure CanQueryRevisions = static function (string $elementType): bool {
        return GqlHelper::canQueryRevisions();
    };

    /** Includes it when the schema can query disabled, archived, or trashed elements. */
    public const Closure CanQueryInactiveElements = static function (string $elementType): bool {
        return GqlHelper::canQueryInactiveElements();
    };

    /** Includes it when the schema can query all users. */
    public const Closure CanQueryAllUsers = static function (string $elementType): bool {
        return GqlHelper::canQueryAllUsers();
    };

    /**
     * @param  (Closure(class-string<ElementInterface>): bool)|null  $gate  Decides whether an element type’s query arguments or interface fields include it. Mutation arguments ignore it.
     * @param  bool  $write  Whether element mutations get a writable property as an argument automatically. Mutations that only accept it in some contexts can add it explicitly instead.
     * @param  bool  $exclude  Whether to keep the param or property out of GraphQL.
     */
    public function __construct(
        public readonly ?Closure $gate = null,
        public readonly bool $write = true,
        public readonly bool $exclude = false,
    ) {}

    /**
     * Returns how GraphQL exposes a param or property, or `null` if it’s excluded.
     */
    public static function of(ElementQueryParam|ElementProperty $member): ?self
    {
        $gql = $member->companion(self::class) ?? new self;

        return $gql->exclude ? null : $gql;
    }

    /**
     * Returns whether an element type gets the param or property.
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    public function allows(string $elementType): bool
    {
        return $this->gate === null || ($this->gate)($elementType);
    }
}
