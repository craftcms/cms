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
    public static function getDirective(): GqlDirective
    {
        return new self([
            'name' => static::getName(),
            'locations' => [
                DirectiveLocation::FIELD,
            ]
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'mockDirective';
    }

    /**
     * @inheritdoc
     */
    public static function applyDirective($source, $value, array $arguments, ResolveInfo $resolveInfo)
    {
        $prefix = $arguments['prefix'] ?? 'mock';
        return $prefix.$value;
    }


}
