<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant\Content;

use CraftCms\Cms\Ai\Contracts\AssistantContextTypeInterface;
use CraftCms\Cms\Ai\Data\AssistantContext;
use Illuminate\Http\Request;

/**
 * Elements in the AI assistant’s context, as the user has them in their editors, and the tools that read the one
 * open in their editor and propose changes to them.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class ElementContextType implements AssistantContextTypeInterface
{
    public function __construct(
        private ElementContexts $elementContexts,
        private Proposals $proposals,
    ) {}

    public static function handle(): string
    {
        return 'craft:element';
    }

    public function resolve(Request $request, array $items): ?AssistantContext
    {
        $contexts = $this->elementContexts->resolve($request, $items);

        if ($contexts === []) {
            return null;
        }

        $description = $this->elementContexts->describe($contexts);
        $open = array_values(array_filter($contexts, static fn (ElementContext $context): bool => $context->open));
        $tools = $open === [] ? [] : [new ReadEditorElement($this->elementContexts, $open)];

        if (! array_any($contexts, static fn (ElementContext $context): bool => $context->editable)) {
            return new AssistantContext("### Elements\n\n$description", $tools);
        }

        return new AssistantContext(
            <<<MARKDOWN
                ### Elements

                To change these elements, or the elements nested in them, call propose_changes: the user reviews each change and applies it to their editor themselves, so never say you’ve saved it. Don’t change these elements with other tools, as the user may have unsaved changes in their editor. When the user asks for content, wording, or edits for these elements, propose them with propose_changes, so they can apply them, rather than only writing them out.

                $description
                MARKDOWN,
            [...$tools, new ProposeChanges($this->proposals, $contexts)],
        );
    }
}
