<?php
namespace craft\gql\directives;

use Craft;
use craft\gql\GqlEntityRegistry;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\Type;

/**
 * Class Transform
 */
class Transform extends BaseDirective
{
    /**
     * @inheritdoc
     */
    public static function getDirective(): Directive
    {
        if ($type = GqlEntityRegistry::getEntity(self::class)) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(static::getName(), new self([
            'name' => static::getName(),
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'args' => [
                new FieldArgument([
                    'name' => 'handle',
                    'type' => Type::string(),
                ]),
             new FieldArgument([
                    'name' => 'width',
                    'type' => Type::int(),
                ]),
             new FieldArgument([
                    'name' => 'height',
                    'type' => Type::int(),
                ]),
             new FieldArgument([
                    'name' => 'mode',
                    'type' => Type::string(),
                ]),
             new FieldArgument([
                    'name' => 'position',
                    'type' => Type::string(),
                ]),
             new FieldArgument([
                    'name' => 'interlace',
                    'type' => Type::string(),
                ]),
             new FieldArgument([
                    'name' => 'quality',
                    'type' => Type::int(),
                ]),
             new FieldArgument([
                    'name' => 'format',
                    'type' => Type::string(),
                ]),
            ],
        ]));

        return $type;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'transform';
    }

    /**
     * @inheritdoc
     */
    public static function applyDirective($source, $value, array $arguments)
    {
        if (!empty($arguments['handle'])) {
            $transform = $arguments['handle'];
        } else {
            $transform = $arguments;
        }

        return Craft::$app->getAssets()->getAssetUrl($source, $transform);
    }


}
