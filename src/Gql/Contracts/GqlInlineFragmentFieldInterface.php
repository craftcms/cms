<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Contracts;

use CraftCms\Cms\Field\Contracts\FieldInterface;

interface GqlInlineFragmentFieldInterface extends FieldInterface
{
    public function getGqlFragmentEntityByName(string $fragmentName): GqlInlineFragmentInterface;
}
