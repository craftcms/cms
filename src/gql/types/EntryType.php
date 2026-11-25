<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types;

use Craft;
use craft\gql\base\ObjectType;
use craft\gql\base\SingularTypeInterface;
use craft\gql\GqlEntityRegistry;
use craft\models\EntryType as EntryTypeModel;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

/**
 * Class EntryType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class EntryType extends ObjectType implements SingularTypeInterface
{
    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'EntryType';
    }

    /**
     * @inheritdoc
     */
    public static function getType(): Type
    {
        return GqlEntityRegistry::getOrCreate(self::getName(), fn() => new self([
            'name' => self::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the type for entry types.',
        ]));
    }

    /**
     * Returns the type’s field definitions.
     *
     * @return array
     */
    public static function getFieldDefinitions(): array
    {
        $fields = [
            'id' => [
                'name' => 'id',
                'type' => Type::nonNull(Type::int()),
                'description' => 'The ID of the entry type.',
            ],
            'uid' => [
                'name' => 'uid',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The UUID of the entry type.',
            ],
            'name' => [
                'name' => 'name',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The name of the entry type.',
            ],
            'handle' => [
                'name' => 'handle',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The handle of the entry type.',
            ],
            'description' => [
                'name' => 'description',
                'type' => Type::string(),
                'description' => 'The description of the entry type.',
            ],
            'icon' => [
                'name' => 'icon',
                'type' => Type::string(),
                'description' => 'The icon of the entry type.',
            ],
            'color' => [
                'name' => 'color',
                'type' => Type::string(),
                'description' => 'The color of the entry type.',
            ],
            'hasTitleField' => [
                'name' => 'hasTitleField',
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'Whether entries of this type have a Title field.',
            ],
        ];

        return Craft::$app->getGql()->prepareFieldDefinitions($fields, self::getName());
    }

    /**
     * @inheritdoc
     */
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var EntryTypeModel $source */
        return match ($resolveInfo->fieldName) {
            'color' => $source->color?->value,
            default => parent::resolve($source, $arguments, $context, $resolveInfo),
        };
    }
}
