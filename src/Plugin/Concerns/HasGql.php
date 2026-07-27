<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Gql\Contracts\SingularTypeInterface;
use CraftCms\Cms\Gql\Directives\Directive;
use CraftCms\Cms\Gql\GqlDirectives;
use CraftCms\Cms\Gql\GqlMutations;
use CraftCms\Cms\Gql\GqlQueries;
use CraftCms\Cms\Gql\GqlTypes;
use CraftCms\Cms\Gql\Mutations\Mutation;
use CraftCms\Cms\Gql\Queries\Query;
use CraftCms\Cms\Plugin\Plugin;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasGql
{
    /** @var class-string<SingularTypeInterface>[] */
    protected array $gqlTypes = [];

    /** @var class-string<Query>[] */
    protected array $gqlQueries = [];

    /** @var class-string<Mutation>[] */
    protected array $gqlMutations = [];

    /** @var class-string<Directive>[] */
    protected array $gqlDirectives = [];

    public function bootHasGql(): void
    {
        $this->app->make(GqlTypes::class)->register(...$this->gqlTypes);
        $this->app->make(GqlQueries::class)->register(...$this->gqlQueries);
        $this->app->make(GqlMutations::class)->register(...$this->gqlMutations);
        $this->app->make(GqlDirectives::class)->register(...$this->gqlDirectives);
    }
}
