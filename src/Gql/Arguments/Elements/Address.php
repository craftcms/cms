<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments\Elements;

use CraftCms\Cms\Address\Elements\Address as AddressElement;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Gql\Arguments\ElementArguments;
use CraftCms\Cms\Gql\Contracts\GqlInlineFragmentFieldInterface;
use CraftCms\Cms\Gql\Types\QueryArgument;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\Type;

/** @phpstan-import-type ArgumentConfig from Argument */
class Address extends ElementArguments
{
    #[\Override]
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

    #[\Override]
    public static function getContentArguments(): array
    {
        $contentArguments = [];

        $contentFields = app(Fields::class)->getLayoutByType(AddressElement::class)->getCustomFields();

        foreach ($contentFields as $contentField) {
            if (! $contentField instanceof GqlInlineFragmentFieldInterface) {
                $contentArguments[$contentField->handle] = $contentField->getContentGqlQueryArgumentType();
            }
        }

        return array_merge(parent::getContentArguments(), $contentArguments);
    }

    #[\Override]
    /** @return array<string, ArgumentConfig> */
    public static function getRevisionArguments(): array
    {
        return [];
    }
}
