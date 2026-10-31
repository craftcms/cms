<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments\Elements;

use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Gql\Arguments\ElementArguments;
use CraftCms\Cms\Gql\Contracts\GqlInlineFragmentFieldInterface;
use CraftCms\Cms\User\Elements\User as UserElement;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\Type;

/**
 * @phpstan-import-type ArgumentConfig from Argument
 *
 * @since 6.0.0
 */
class User extends ElementArguments
{
    #[\Override]
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), self::getContentArguments(), static::queryParamArguments(UserElement::class, [
            'email',
            'username',
            'fullName',
            'firstName',
            'lastName',
            'hasPhoto',
            'assetUploaders',
            'authors',
            'groupId',
            'group',
            'affiliatedSite',
            'affiliatedSiteId',
        ]));
    }

    #[\Override]
    public static function getContentArguments(): array
    {
        $contentArguments = [];

        $contentFields = app(Fields::class)->getLayoutByType(UserElement::class)->getCustomFields();

        foreach ($contentFields as $contentField) {
            if (! $contentField instanceof GqlInlineFragmentFieldInterface) {
                $contentArguments[$contentField->handle] = $contentField->getContentGqlQueryArgumentType();
            }
        }

        return array_merge(parent::getContentArguments(), $contentArguments);
    }

    #[\Override]
    /** @return array<string, ArgumentConfig> */
    public static function getDraftArguments(): array
    {
        return [];
    }

    #[\Override]
    /** @return array<string, ArgumentConfig> */
    public static function getRevisionArguments(): array
    {
        return [];
    }
}
