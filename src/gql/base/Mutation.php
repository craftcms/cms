<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

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
     * @param array $contentFields
     * @return void
     */
    protected static function prepareResolver(MutationResolver $resolver, array $contentFields)
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
