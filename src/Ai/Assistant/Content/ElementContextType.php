<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant\Content;

use CraftCms\Cms\Ai\Assistant\ViewableAssets;
use CraftCms\Cms\Ai\Contracts\AssistantContextTypeInterface;
use CraftCms\Cms\Ai\Data\AssistantContext;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\FileKind;
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
        private ViewableAssets $viewable,
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

        if ($imageTool = $this->viewEditorImage($request, $items, $contexts)) {
            $tools[] = $imageTool;
            $description .= "\n\nTo see the image of the asset open in the editor, call view_editor_image. Look at it rather than asking the user what it shows, but only when their request needs it.";
        }

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

    /**
     * Returns the tool that shows the model the image of the asset open in the user’s editor, if it’s an image they can
     * view, and the model can see it.
     *
     * The asset is found from the open editor’s item rather than its context, as an item the message was sent with for
     * the same asset takes the context’s place.
     *
     * @param  array<mixed>  $items
     * @param  list<ElementContext>  $contexts
     */
    private function viewEditorImage(Request $request, array $items, array $contexts): ?ViewEditorImage
    {
        $item = array_find($items, static fn (mixed $item): bool => is_array($item)
            && ($item['open'] ?? false) === true
            && ($item['earlier'] ?? false) !== true);
        $asset = $item === null ? null : array_find(
            array_map(static fn (ElementContext $context): mixed => $context->element, $contexts),
            static fn (mixed $element): bool => $element instanceof Asset
                && $element->id === ($item['elementId'] ?? null)
                && (! is_int($item['siteId'] ?? null) || $element->siteId === $item['siteId']),
        );
        $threadId = $request->input('threadId');
        $user = $request->craftUser()?->asElement();

        if (
            ! $asset instanceof Asset
            || $asset->kind !== FileKind::Image->value
            || ! is_string($threadId)
            || ! $user?->can('view', $asset)
            || ! $this->viewable->canView($asset)
        ) {
            return null;
        }

        return new ViewEditorImage($this->viewable, $threadId, $asset);
    }
}
