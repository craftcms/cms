<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai;

use CraftCms\Cms\Ai\Assistant\Content\ElementContextType;
use CraftCms\Cms\Ai\Contracts\AssistantContextTypeInterface;
use CraftCms\Cms\Ai\Data\AssistantContext;
use CraftCms\Cms\Component\TypeRegistry;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Registers the kinds of items the user can add to the AI assistant’s context, keyed by their handles.
 *
 * ```php
 * public function boot(AssistantContextTypes $contextTypes): void
 * {
 *     $contextTypes->register(OrderContext::class);
 * }
 * ```
 *
 * @extends TypeRegistry<AssistantContextTypeInterface>
 *
 * @since 6.0.0
 */
#[Singleton]
class AssistantContextTypes extends TypeRegistry
{
    protected const string CONTRACT = AssistantContextTypeInterface::class;

    protected const array DEFAULT_TYPES = [ElementContextType::class];

    protected const array PROTECTED_TYPES = [ElementContextType::class];

    /**
     * Resolves the items in a message’s context with their context types, leaving out items of unknown types.
     *
     * @param  array<mixed>  $items  The items, as `{type, ...}`.
     * @return list<AssistantContext>
     *
     * @internal
     */
    public function resolve(Request $request, array $items): array
    {
        $itemsByType = [];

        foreach ($items as $item) {
            if (is_array($item) && is_string($item['type'] ?? null)) {
                $itemsByType[$item['type']][] = $item;
            }
        }

        $contexts = [];

        foreach ($itemsByType as $handle => $typeItems) {
            $type = $this->typeByIdentity($handle);
            $context = $type ? app($type)->resolve($request, $typeItems) : null;

            if ($context !== null) {
                $contexts[] = $context;
            }
        }

        return $contexts;
    }

    /** @param class-string<AssistantContextTypeInterface> $type */
    #[\Override]
    protected function identity(string $type): string
    {
        $handle = $type::handle();

        if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_\-:]*$/', $handle)) {
            throw new InvalidArgumentException("The AI assistant context type handle [{$handle}] may only contain letters, numbers, underscores, hyphens, and colons, and must start with a letter.");
        }

        return $handle;
    }
}
