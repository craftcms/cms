<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Handlers;

use CraftCms\Cms\Gql\ArgumentManager;
use CraftCms\Cms\Gql\Contracts\ArgumentHandlerInterface;

abstract class ArgumentHandler implements ArgumentHandlerInterface
{
    protected ArgumentManager $argumentManager;

    protected string $argumentName;

    public function setArgumentManager(ArgumentManager $argumentManager): void
    {
        $this->argumentManager = $argumentManager;
    }

    abstract protected function handleArgument(mixed $argumentValue): mixed;

    public function handleArgumentCollection(array $argumentList = []): array
    {
        $argumentList[$this->argumentName] = $this->handleArgument($argumentList[$this->argumentName]);

        return $argumentList;
    }
}
