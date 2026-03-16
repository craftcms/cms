<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Handlers;

use CraftCms\Cms\User\Elements\User;
use Override;

class RelatedUsers extends RelationArgumentHandler
{
    #[Override]
    protected string $argumentName = 'relatedToUsers';

    #[Override]
    protected function handleArgument($argumentValue): mixed
    {
        $argumentValue = parent::handleArgument($argumentValue);

        return $this->getIds(User::class, $argumentValue);
    }
}
