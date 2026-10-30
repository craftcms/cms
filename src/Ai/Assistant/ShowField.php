<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiResolver;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * The AI assistant’s tool that shows the user where a field is in an element’s editor: a link that highlights the
 * field in the element’s open editor, or opens the editor at the field.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class ShowField implements Tool
{
    public const string NAME = 'show_field';

    public function __construct(
        private ViewableElements $elements,
        private FieldLayoutCompiler $compiler,
    ) {}

    public function name(): string
    {
        return self::NAME;
    }

    public function description(): string
    {
        return 'Shows the user a link to a field in an element’s editor, which highlights the field when they follow it, opening the editor first if it isn’t open. Use it when they ask where to find or set something on an element.';
    }

    public function handle(Request $request): string
    {
        $arguments = $request->all();
        $found = $this->elements->find($arguments);

        if ($found === null) {
            return Json::encode(['message' => 'The element couldn’t be found, or the user can’t view it.']);
        }

        [$type, $element] = $found;
        $handle = is_string($arguments['field'] ?? null) ? $arguments['field'] : '';
        $field = $this->findField($element, [['fields', $handle], [$handle]]);

        if ($field === null) {
            return Json::encode(['message' => "The element’s editor doesn’t show a “{$handle}” field."]);
        }

        return Json::encode([
            'message' => 'Showed the field.',
            'element' => [
                'type' => $type,
                'id' => $element->id,
                'canonicalId' => $element->getCanonicalId(),
                'siteId' => $element->siteId,
                'label' => $element->getUiLabel(),
                'cpEditUrl' => $element->getCpEditUrl(),
            ],
            'field' => [
                'label' => is_string($field->props['label'] ?? null) ? $field->props['label'] : $handle,
                // The path of the field’s control in the editor’s form.
                'path' => $field->control->path ?? [],
            ],
        ]);
    }

    /**
     * Finds a field the element’s editor shows, in its content or its sidebar, by the path of its control.
     *
     * @param  list<list<string>>  $paths
     */
    private function findField(ElementInterface $element, array $paths): ?NodePayload
    {
        $context = new UiContext(namespace: []);
        $layout = $element->getFieldLayout();
        $sidebar = $element->sidebarUi($context);
        $payloads = [
            $layout ? $this->compiler->compile($layout, $element, $context) : null,
            $sidebar ? app(UiResolver::class)->resolve($sidebar, $context) : null,
        ];

        foreach ($payloads as $payload) {
            if ($payload && $found = $this->findNode($payload->nodes, $paths)) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @param  list<NodePayload>  $nodes
     * @param  list<list<string>>  $paths
     */
    private function findNode(array $nodes, array $paths): ?NodePayload
    {
        foreach ($nodes as $node) {
            if ($node->control && in_array($node->control->path, $paths, true)) {
                return $node;
            }

            if ($found = $this->findNode($node->children ?? [], $paths)) {
                return $found;
            }
        }

        return null;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('The element type, as elements.schema lists it, such as entries.')->required(),
            'id' => $schema->integer()->description('The element’s ID.')->required(),
            'siteId' => $schema->integer()->description('The site to show it in. Defaults to the primary site.'),
            'field' => $schema->string()->description('The handle of a custom field, or a built-in attribute the editor shows as a field, such as title, slug, or postDate.')->required(),
        ];
    }
}
