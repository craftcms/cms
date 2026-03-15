<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Mutations;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Gql\Concerns\HasGqlType;
use CraftCms\Cms\Gql\Resolvers\ElementMutationResolver;
use CraftCms\Cms\Gql\Resolvers\MutationResolver;

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
            $configArray = is_array($contentFieldType) ? $contentFieldType : $contentFieldType->config;

            if (is_array($configArray) && ! empty($configArray['normalizeValue'])) {
                $resolver->setValueNormalizer($handle, $configArray['normalizeValue']);
            }
        }

        $resolver->setResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY, $fieldList);
    }
}
