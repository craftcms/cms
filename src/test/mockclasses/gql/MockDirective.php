<?php
namespace craft\test\mockclasses\gql;

use Craft;
use craft\gql\directives\BaseDirective;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive;

/**
 * Class MockDirective
 */
class MockDirective extends BaseDirective
{
    /**
     * @inheritdoc
     */
    public static function getDirective(): Directive
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
    public static function applyDirective($source, $value, array $arguments)
    {
    }


}
