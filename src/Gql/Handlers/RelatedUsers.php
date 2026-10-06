<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Handlers;

use CraftCms\Cms\User\Elements\User;
use Override;

/**
 * @since 6.0.0
 */
class RelatedUsers extends RelationArgumentHandler
{
    #[Override]
    protected string $argumentName = 'relatedToUsers';

    #[Override]
    protected function handleArgument(mixed $argumentValue): mixed
    {
        $argumentValue = parent::handleArgument($argumentValue);

        return $this->getIds(User::class, $argumentValue);
    }
}
