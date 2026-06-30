<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Mutations;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Gql\Concerns\HasGqlType;
use CraftCms\Cms\Gql\Resolvers\ElementMutationResolver;
use CraftCms\Cms\Gql\Resolvers\MutationResolver;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\WrappingType;

abstract class Mutation
{
    use HasGqlType;

    /**
     * Returns the mutations defined by the class as an array.
     */
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

            if (! empty($configArray) && ! empty($configArray['normalizeValue'])) {
                $resolver->setValueNormalizer($handle, $configArray['normalizeValue']);
            }
        }

        $resolver->setResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY, $fieldList);
    }
}
