<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use craft\base\FieldInterface;

/**
 * Class Mutation
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class Mutation
{
    use GqlTypeTrait;

    /**
     * Returns the mutations defined by the class as an array.
     *
     * @return array
     */
    abstract public static function getMutations(): array;

    /**
     * Load content fields and value normalizers on the resolver, based on content fields.
     *
     * @param MutationResolver $resolver
     * @param FieldInterface[] $contentFields
     */
    protected static function prepareResolver(MutationResolver $resolver, array $contentFields): void
    {
        $fieldList = [];

        foreach ($contentFields as $contentField) {
            $contentFieldType = $contentField->getContentGqlMutationArgumentType();
            $handle = $contentField->handle;
            $fieldList[$handle] = $contentFieldType;
            $configArray = is_array($contentFieldType) ? $contentFieldType : $contentFieldType->config;

            if (is_array($configArray) && !empty($configArray['normalizeValue'])) {
                $resolver->setValueNormalizer($handle, $configArray['normalizeValue']);
            }
        }

        $resolver->setResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY, $fieldList);
    }
}
