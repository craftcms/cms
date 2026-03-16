<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Handlers;

use CraftCms\Cms\Entry\Elements\Entry;
use Override;

class RelatedEntries extends RelationArgumentHandler
{
    #[Override]
    protected string $argumentName = 'relatedToEntries';

    #[Override]
    protected function handleArgument($argumentValue): mixed
    {
        $argumentValue = parent::handleArgument($argumentValue);

        return $this->getIds(Entry::class, $argumentValue);
    }
}
