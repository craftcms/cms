<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Gql;

use CraftCms\Cms\Gql\Directives\Directive;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class MockDirective
 */
class MockDirective extends Directive
{
    /**
     * {@inheritdoc}
     */
    public static function create(): GqlDirective
    {
        return new self([
            'name' => static::name(),
            'locations' => [
                DirectiveLocation::FIELD,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function name(): string
    {
        return 'mockDirective';
    }

    /**
     * {@inheritdoc}
     */
    public static function apply(mixed $source, mixed $value, array $arguments, ResolveInfo $resolveInfo): string
    {
        $prefix = $arguments['prefix'] ?? 'mock';

        return $prefix.$value;
    }
}
