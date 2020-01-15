<?php

namespace craft\test\mockclasses\gql;

use craft\gql\base\Directive;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\ResolveInfo;

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
            ]
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
    public static function apply($source, $value, array $arguments, ResolveInfo $resolveInfo)
    {
        $prefix = $arguments['prefix'] ?? 'mock';
        return $prefix . $value;
    }
}
