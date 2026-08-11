<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Mutations;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Gql\Concerns\HasGqlType;
use CraftCms\Cms\Gql\Resolvers\ElementMutationResolver;
use CraftCms\Cms\Gql\Resolvers\MutationResolver;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\WrappingType;

/** @phpstan-import-type UnnamedFieldDefinitionConfig from FieldDefinition */
abstract class Mutation
{
    use HasGqlType;

    /** @return array<string, UnnamedFieldDefinitionConfig> */
    abstract public static function getMutations(): array;

    /**
     * @param  FieldInterface[]  $contentFields
     */
    protected static function prepareResolver(MutationResolver $resolver, array $contentFields): void
    {
        $fieldList = [];

        foreach ($contentFields as $contentField) {
            $contentFieldType = $contentField->getContentGqlMutationArgumentType();
            $handle = $contentField->handle;
            $fieldList[$handle] = $contentFieldType;
            if (is_array($contentFieldType)) {
                $configArray = $contentFieldType;
            } else {
                $innerType = $contentFieldType instanceof WrappingType
                    ? $contentFieldType->getWrappedType()
                    : $contentFieldType;
                $configArray = $innerType instanceof InputObjectType ? $innerType->config : [];
            }

            if ($normalizer = self::valueNormalizer($configArray)) {
                $resolver->setValueNormalizer($handle, $normalizer);
            }
        }

        $resolver->setResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY, $fieldList);
    }

    /** @param array{normalizeValue?: callable(mixed): mixed, ...} $config */
    private static function valueNormalizer(array $config): ?callable
    {
        return $config['normalizeValue'] ?? null;
    }
}
