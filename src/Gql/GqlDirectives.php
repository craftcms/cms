<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Directives\Directive;
use CraftCms\Cms\Gql\Directives\FormatDateTime;
use CraftCms\Cms\Gql\Directives\Markdown;
use CraftCms\Cms\Gql\Directives\Money;
use CraftCms\Cms\Gql\Directives\ParseRefs;
use CraftCms\Cms\Gql\Directives\StripTags;
use CraftCms\Cms\Gql\Directives\Transform;
use CraftCms\Cms\Gql\Directives\Trim;
use GraphQL\Type\Definition\Directive as GqlDirective;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;

/**
 * Registers directive classes available to GraphQL schemas.
 *
 * ```php
 * public function boot(GqlDirectives $directives): void
 * {
 *     $directives->register(MyDirective::class);
 * }
 * ```
 *
 * @extends TypeRegistry<Directive>
 */
#[Singleton]
class GqlDirectives extends TypeRegistry
{
    protected const string CONTRACT = Directive::class;

    protected const array DEFAULT_TYPES = [
        FormatDateTime::class,
        Markdown::class,
        Money::class,
        StripTags::class,
        Trim::class,
        ParseRefs::class,
        Transform::class,
    ];

    /** @return Collection<int, class-string<Directive>> */
    public function forSchema(?GqlSchema $schema): Collection
    {
        $scope = $schema === null ? [] : $schema->scope;

        return parent::types()->reject(fn (string $directive) => match ($directive) {
            ParseRefs::class => ! in_array('directive:parseRefs', $scope, true),
            Transform::class => ! in_array('directive:transform', $scope, true),
            default => false,
        });
    }

    /** @param class-string<Directive> $type */
    #[\Override]
    protected function identity(string $type): string
    {
        return $type::name();
    }

    #[\Override]
    protected function reservedIdentities(): array
    {
        return array_values(array_map(
            fn (GqlDirective $directive) => $directive->name,
            GqlDirective::builtInDirectives(),
        ));
    }
}
