<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Gql;

use CraftCms\Cms\Gql\Directives\Directive;
use CraftCms\Cms\Gql\GqlEntityRegistry;
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
        return GqlEntityRegistry::getOrCreate(static::name(), fn () => new self([
            'name' => static::name(),
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'args' => [
                [
                    'name' => 'prefix',
                    'type' => MockType::getType(),
                ],
            ],
        ]));
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
