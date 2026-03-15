<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Contracts;

interface GqlInlineFragmentFieldInterface
{
    public function getGqlFragmentEntityByName(string $fragmentName): GqlInlineFragmentInterface;
}
