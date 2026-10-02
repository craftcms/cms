<?php

namespace craft\test\mockclasses\gql;

use craft\gql\base\Directive;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

/**
 * Class MockDirective
 */
class MockDirective extends Directive
{
    /**
     * @inheritdoc
     */
    public static function create(): GqlDirective
    {
        return new self([
            'name' => static::name(),
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'args' => [
                [
                    'name' => 'prefix',
                    'type' => Type::string(),
                ],
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function name(): string
    {
        return 'mockDirective';
    }

    /**
     * @inheritdoc
     */
    public static function apply(mixed $source, mixed $value, array $arguments, ResolveInfo $resolveInfo): string
    {
        $prefix = $arguments['prefix'] ?? 'mock';
        return $prefix . $value;
    }
}
