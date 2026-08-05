<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Handlers;

use CraftCms\Cms\Asset\Elements\Asset;
use Override;

class RelatedAssets extends RelationArgumentHandler
{
    #[Override]
    protected string $argumentName = 'relatedToAssets';

    #[Override]
    protected function handleArgument(mixed $argumentValue): mixed
    {
        $argumentValue = parent::handleArgument($argumentValue);

        return $this->getIds(Asset::class, $argumentValue);
    }
}
