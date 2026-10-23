<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai;

use CraftCms\Cms\Ai\Contracts\TaskInterface;
use CraftCms\Cms\Ai\Tasks\AskAi;
use CraftCms\Cms\Component\TypeRegistry;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Registers AI task classes, keyed by their handles.
 *
 * ```php
 * public function boot(AiTasks $tasks): void
 * {
 *     $tasks->register(DescribeImage::class);
 * }
 * ```
 *
 * @extends TypeRegistry<TaskInterface>
 *
 * @since 6.0.0
 */
#[Singleton]
class AiTasks extends TypeRegistry
{
    protected const string CONTRACT = TaskInterface::class;

    protected const array DEFAULT_TYPES = [AskAi::class];

    /**
     * @return Collection<string, class-string<TaskInterface>>
     */
    public function tasksByHandle(): Collection
    {
        return $this->typesByIdentity();
    }

    /**
     * @return class-string<TaskInterface>|null
     */
    public function getTaskByHandle(string $handle): ?string
    {
        return $this->typeByIdentity($handle);
    }

    /** @param class-string<TaskInterface> $type */
    #[\Override]
    protected function identity(string $type): string
    {
        $handle = $type::handle();

        if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_\-:]*$/', $handle)) {
            throw new InvalidArgumentException("The AI task handle [{$handle}] may only contain letters, numbers, underscores, hyphens, and colons, and must start with a letter.");
        }

        return $handle;
    }
}
