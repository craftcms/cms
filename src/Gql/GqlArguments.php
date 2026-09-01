<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use Closure;
use CraftCms\Cms\Gql\Contracts\ArgumentHandlerInterface;
use CraftCms\Cms\Gql\Handlers\RelatedAssets;
use CraftCms\Cms\Gql\Handlers\RelatedEntries;
use CraftCms\Cms\Gql\Handlers\RelatedUsers;
use CraftCms\Cms\Gql\Handlers\Site;
use CraftCms\Cms\Gql\Handlers\SiteId;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Registers handlers for custom GraphQL query arguments.
 *
 * ```php
 * public function boot(GqlArguments $arguments): void
 * {
 *     $arguments->register('relatedToProducts', RelatedProducts::class);
 * }
 * ```
 */
#[Singleton]
class GqlArguments
{
    /** @var array<string, class-string<ArgumentHandlerInterface>|Closure> */
    private array $handlers = [];

    public function __construct()
    {
        $this->register('relatedToEntries', RelatedEntries::class);
        $this->register('relatedToAssets', RelatedAssets::class);
        $this->register('relatedToUsers', RelatedUsers::class);
        $this->register('site', Site::class);
        $this->register('siteId', SiteId::class);
    }

    /** @param class-string<ArgumentHandlerInterface>|Closure $handler */
    public function register(string $argumentName, string|Closure $handler): void
    {
        $this->validateName($argumentName);
        $this->validateHandler($handler);

        $this->handlers[$argumentName] = $handler;
    }

    public function remove(string ...$argumentNames): void
    {
        foreach ($argumentNames as $argumentName) {
            $this->validateName($argumentName);
        }

        foreach ($argumentNames as $argumentName) {
            unset($this->handlers[$argumentName]);
        }
    }

    /** @return Collection<string, class-string<ArgumentHandlerInterface>|Closure> */
    public function handlers(): Collection
    {
        return new Collection($this->handlers);
    }

    private function validateName(string $argumentName): void
    {
        if ($argumentName === '') {
            throw new InvalidArgumentException('Argument handler names cannot be empty.');
        }
    }

    /** @param class-string<ArgumentHandlerInterface>|Closure $handler */
    private function validateHandler(string|Closure $handler): void
    {
        if (is_string($handler) && ! is_a($handler, ArgumentHandlerInterface::class, true)) {
            throw new InvalidArgumentException(sprintf('Argument handler [%s] must implement [%s].', $handler, ArgumentHandlerInterface::class));
        }
    }
}
