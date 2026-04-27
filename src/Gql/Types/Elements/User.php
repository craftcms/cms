<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Elements;

use CraftCms\Cms\Gql\Interfaces\Elements\User as UserInterface;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User as UserElement;
use GraphQL\Type\Definition\ResolveInfo;
use Override;

class User extends Element
{
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            UserInterface::getType(),
        ];

        parent::__construct($config);
    }

    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var UserElement $source */
        $fieldName = $resolveInfo->fieldName;

        return match ($fieldName) {
            'preferences' => Json::encode($source->getPreferences()),
            'affiliatedSiteHandle' => $source->getAffiliatedSite()?->handle,
            default => parent::resolve($source, $arguments, $context, $resolveInfo),
        };
    }
}
