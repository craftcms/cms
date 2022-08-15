<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\arguments\elements;

use Craft;
use craft\base\GqlInlineFragmentFieldInterface;
use craft\elements\Address as AddressElement;
use craft\gql\base\ElementArguments;
use craft\gql\types\QueryArgument;
use GraphQL\Type\Definition\Type;

/**
 * Class Address
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Address extends ElementArguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), self::getContentArguments(), [
            'ownerId' => [
                'name' => 'ownerId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the addresses’ owners.',
            ],
            'countryCode' => [
                'name' => 'countryCode',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the addresses’ country codes.',
            ],
            'administrativeArea' => [
                'name' => 'administrativeArea',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the addresses’ administrative areas.',
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getContentArguments(): array
    {
        $contentArguments = [];

        $contentFields = Craft::$app->getFields()->getLayoutByType(AddressElement::class)->getCustomFields();

        foreach ($contentFields as $contentField) {
            if (!$contentField instanceof GqlInlineFragmentFieldInterface) {
                $contentArguments[$contentField->handle] = $contentField->getContentGqlQueryArgumentType();
            }
        }

        return array_merge(parent::getContentArguments(), $contentArguments);
    }

    /**
     * @inheritdoc
     */
    public static function getRevisionArguments(): array
    {
        return [];
    }
}
