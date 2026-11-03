<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Contracts;

use CraftCms\Cms\Ai\AssistantContextTypes;
use CraftCms\Cms\Ai\Data\AssistantContext;
use Illuminate\Http\Request;

/**
 * A kind of item the user can add to the AI assistant’s context, such as an element.
 *
 * The control panel sends each item in the context with the user’s next message, as `{type, ref, ...data}`, where
 * `data` is what was passed to `Cp.$assistant.addContext()`. The context type turns its items into a description for
 * the model, and can give the model tools that work with them.
 *
 * The items that earlier messages in the conversation were sent with come too, after the latest message’s, marked with
 * `earlier: true`. They’re `{type, ref, ...reference}`, where `reference` is what the item’s `reference` returned
 * when its message was sent. Items without a reference aren’t sent again.
 *
 * Register context types with {@see AssistantContextTypes}:
 *
 * ```php
 * public function boot(AssistantContextTypes $contextTypes): void
 * {
 *     $contextTypes->register(OrderContext::class);
 * }
 * ```
 *
 * They’re resolved from the container for each message.
 *
 * @since 6.0.0
 */
interface AssistantContextTypeInterface
{
    /**
     * Returns the context type’s globally unique handle, such as `commerce:order`, which the control panel sends as
     * each item’s `type`.
     *
     * Prefix it with your plugin handle. It may only contain letters, numbers, underscores, hyphens, and colons, and
     * must start with a letter.
     */
    public static function handle(): string;

    /**
     * Describes the items of this type in a message’s context to the model, as of now.
     *
     * The items come from the browser, so check that the user can view each one, and leave out those they can’t.
     * The description goes in the model’s instructions: say what each item is, and what the model can do with it.
     * Tools are only offered while the items are in the context, so their names must be unique among the assistant’s
     * tools.
     *
     * @param  list<array<string, mixed>>  $items  The items, as the control panel sent them.
     * @return AssistantContext|null The description and tools, or `null` if none of the items can be described.
     */
    public function resolve(Request $request, array $items): ?AssistantContext;
}
