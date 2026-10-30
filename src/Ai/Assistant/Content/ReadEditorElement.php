<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant\Content;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * The AI assistant’s tool that reads the element open in the user’s editor, as they have it there, including changes
 * they haven’t saved. The element is only named in the context until the model reads it, so questions about something
 * else don’t send it.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class ReadEditorElement implements Tool
{
    public const string NAME = 'read_editor_element';

    /**
     * @param  list<ElementContext>  $contexts  The element open in the user’s editor.
     */
    public function __construct(
        private ElementContexts $elementContexts,
        private array $contexts,
    ) {}

    public function name(): string
    {
        return self::NAME;
    }

    public function description(): string
    {
        return 'Reads the element the user has open in their editor, as elements.get does, but as they have it there, including changes they haven’t saved.';
    }

    public function handle(Request $request): string
    {
        return $this->elementContexts->describe($this->contexts, read: true);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
