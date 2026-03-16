<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Contracts;

use CraftCms\Cms\Gql\ArgumentManager;

interface ArgumentHandlerInterface
{
    /**
     * @param  array  $argumentList  argument list to be used for the query
     */
    public function handleArgumentCollection(array $argumentList = []): array;

    public function setArgumentManager(ArgumentManager $argumentManager): void;
}
