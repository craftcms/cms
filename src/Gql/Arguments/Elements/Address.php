<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments\Elements;

use CraftCms\Cms\Address\Elements\Address as AddressElement;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Gql\Arguments\ElementArguments;
use CraftCms\Cms\Gql\Contracts\GqlInlineFragmentFieldInterface;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\Type;
use Override;

/**
 * @phpstan-import-type ArgumentConfig from Argument
 *
 * @since 6.0.0
 */
class Address extends ElementArguments
{
    #[Override]
    public static function getArguments(): array
    {
        // Custom field arguments can’t shadow the address’s own arguments.
        return array_merge(self::getContentArguments(), parent::getArguments());
    }

    #[Override]
    protected static function elementType(): string
    {
        return AddressElement::class;
    }

    #[Override]
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
}
