<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Contracts;

interface GqlInlineFragmentInterface
{
    public function getFieldContext(): string;

    public function getEagerLoadingPrefix(): string;
}
