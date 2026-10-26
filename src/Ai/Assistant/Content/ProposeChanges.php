<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant\Content;

use CraftCms\Cms\Support\Json;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Mcp\Exception\ToolCallException;

/**
 * The AI assistant’s tool that proposes changes to an element in the context, which the user reviews and applies to
 * their editor themselves. It takes the attributes and fields the MCP server’s `elements.update` tool does.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class ProposeChanges implements Tool
{
    public const string NAME = 'propose_changes';

    /**
     * @param  list<ElementContext>  $contexts
     */
    public function __construct(
        private Proposals $proposals,
        private array $contexts,
    ) {}

    public function name(): string
    {
        return self::NAME;
    }

    public function description(): string
    {
        return 'Proposes changes to an element in the context, or an element nested in one, which the user reviews and applies to their editor themselves. Takes the attributes and fields elements.update does; call elements.field-schema for an element’s fields. To change a nested entry, pass its own id rather than its field’s value. Nothing is saved.';
    }

    public function handle(Request $request): string
    {
        $arguments = $request->all();

        try {
            $proposals = $this->proposals->propose(
                $this->contexts,
                (int) ($arguments['id'] ?? 0),
                is_array($arguments['attributes'] ?? null) ? $arguments['attributes'] : [],
                is_array($arguments['fields'] ?? null) ? $arguments['fields'] : [],
            );
        } catch (ToolCallException $exception) {
            return Json::encode(['message' => $exception->getMessage(), 'proposals' => []]);
        }

        return Json::encode([
            'message' => $proposals === []
                ? 'Nothing changed.'
                : sprintf('Proposed %d change(s). The user can now review and apply them.', count($proposals)),
            'proposals' => $proposals,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('The ID of the element to change.')->required(),
            'attributes' => $schema->object()->description('Built-in element attributes, as elements.update takes them.'),
            'fields' => $schema->object()->description('Custom field values keyed by field handle, as elements.field-schema describes them. Only include the fields to change.'),
        ];
    }
}
