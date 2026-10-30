<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant\Content;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Mcp\Elements\ElementAdapterRegistry;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Ui\Enums\ControlMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Resolves the elements the user added to the AI assistant’s context, as they have them in their editors, and
 * describes them to the model as the MCP server’s `elements.get` tool does.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class ElementContexts
{
    /**
     * The most elements a message can have in its context.
     */
    public const int MAX = 5;

    public function __construct(
        private ElementAdapterRegistry $adapters,
    ) {}

    /**
     * Returns the elements in a message’s context, leaving out those that can’t be found, that the user can’t view, or
     * that aren’t identified as described.
     *
     * Each item identifies an element as the editor does, with the editor’s namespace and form values, and the paths
     * of the fields to include, such as `fields.summary`, or `null` for all of them:
     *
     * ```
     * {elementType, elementId, siteId, draftId?, ownerId?, fieldId?, ref?, open?, namespace: string[], values: object, fields: string[]|null}
     * ```
     *
     * The editor saves its changes to a draft as they’re made, which nested elements are read from. The values of the
     * element’s own fields are taken from the form, as changes that are still to be saved.
     *
     * Items that earlier messages were sent with are marked `earlier`, and have no form values. The element open in the
     * user’s editor is marked `open`, and only described by name until the model reads it. Both are left out when the
     * element is in the context already.
     *
     * @param  array<mixed>  $items
     * @return list<ElementContext>
     */
    public function resolve(Request $request, array $items): array
    {
        $contexts = [];
        $user = $request->craftUser();

        // Items that don’t resolve, or repeat an element, leave their places to the next, up to a bound on the lookups.
        foreach (array_slice(array_values($items), 0, self::MAX * 3) as $item) {
            if (count($contexts) === self::MAX) {
                break;
            }

            if (! is_array($item) || ! ($element = $this->element($request, $item))) {
                continue;
            }

            $earlier = ($item['earlier'] ?? false) === true;
            $open = ! $earlier && ($item['open'] ?? false) === true;

            if (($earlier || $open) && array_any($contexts, static fn (ElementContext $context): bool => $context->element->id === $element->id
                && $context->element->siteId === $element->siteId)) {
                continue;
            }

            $this->applyFormValues($element, is_array($item['values'] ?? null) ? $item['values'] : []);

            /** @var list<string> $namespace Checked with the element. */
            $namespace = $item['namespace'] ?? [];
            $paths = $item['fields'] ?? null;

            $contexts[] = new ElementContext(
                id: 'e'.(count($contexts) + 1),
                element: $element,
                namespace: $namespace,
                // A nested field brings the field it’s in, as the model sees nested elements within their fields.
                fields: is_array($paths) ? array_values(array_unique(array_filter(array_map(
                    static fn (mixed $path): ?string => is_string($path) ? (explode('.', $path)[1] ?? null) : null,
                    array_filter($paths, static fn (mixed $path): bool => is_string($path) && str_starts_with($path, 'fields.')),
                )))) : null,
                editable: $user !== null && ElementRequest::canSave($element, $user),
                ref: is_string($item['ref'] ?? null) ? $item['ref'] : null,
                earlier: $earlier,
                open: $open,
            );
        }

        return $contexts;
    }

    /**
     * Describes the elements to the model, as `elements.get` returns them, with the elements nested in their fields.
     * The element open in the user’s editor is only named, unless it’s being read.
     *
     * @param  list<ElementContext>  $contexts
     */
    public function describe(array $contexts, bool $read = false): string
    {
        $sections = [];

        foreach ($contexts as $context) {
            $element = $context->element;

            if ($context->open && ! $read) {
                $sections[] = sprintf(
                    '%s: the %s “%s” (ID %d), which the user has open in their editor. Before answering about it or proposing changes to it, read it with %s rather than elements.get, as it has changes they haven’t saved. Don’t read it for questions about something else.%s',
                    $context->id,
                    $element::lowerDisplayName(),
                    $element->getUiLabel(),
                    $element->id,
                    ReadEditorElement::NAME,
                    $context->editable ? '' : ' You can’t propose changes to it.',
                );

                continue;
            }
            $handles = array_map(
                static fn (CustomField $field): string => $field->getField()->handle,
                $element->getFieldLayout()?->getVisibleCustomFieldElements($element) ?? [],
            );
            $fields = $context->fields === null ? $handles : array_values(array_intersect($handles, $context->fields));
            $serialized = $this->adapters->find($element::refHandle())?->serialize($element, $fields);

            $sections[] = implode("\n", [
                sprintf(
                    '%s: the %s “%s”, %s.%s',
                    $context->id,
                    $element::lowerDisplayName(),
                    $element->getUiLabel(),
                    $context->earlier
                        ? 'which an earlier message was sent with, as the editor last saved it'
                        : 'as the user has it in their editor, including changes they haven’t saved',
                    $context->editable ? '' : ' You can’t propose changes to it.',
                ),
                Json::encode($serialized ?? ['id' => $element->id]),
            ]);
        }

        return implode("\n\n", $sections);
    }

    /**
     * Returns the fields of an element that can be added to a message’s context, by the paths a context item’s
     * `fields` takes.
     *
     * @return list<array{value: string, label: string}>
     */
    public function fields(ElementInterface $element): array
    {
        return array_values(array_map(
            static fn (CustomField $field): array => [
                'value' => "fields.{$field->getField()->handle}",
                'label' => $field->label() ?? $field->getField()->name,
            ],
            $element->getFieldLayout()?->getVisibleCustomFieldElements($element) ?? [],
        ));
    }

    /**
     * Sets the form’s values on an element: its title, alternative text, and custom fields that the user can see and
     * edit. Fields that hold nested elements are left out, as setting their values can create drafts of the nested
     * elements. Fields that a value reveals get their own values on the next pass.
     *
     * @param  array<mixed>  $data  The element’s values in the form.
     */
    private function applyFormValues(ElementInterface $element, array $data): void
    {
        $values = is_array($data['fields'] ?? null) ? $data['fields'] : [];
        $applied = [];

        do {
            $appliedAny = false;

            foreach ($element->getFieldLayout()?->getVisibleElementsByType(BaseField::class, $element) ?? [] as $layoutField) {
                $key = $layoutField instanceof CustomField ? $layoutField->getField()->handle : $layoutField->attribute();

                if (isset($applied[$key]) || $layoutField->uiMode($element) !== ControlMode::Editable) {
                    continue;
                }

                $applied[$key] = $appliedAny = true;

                if ($layoutField instanceof CustomField) {
                    if (! $layoutField->getField() instanceof ElementContainerFieldInterface && array_key_exists((string) $key, $values)) {
                        $element->setFieldValueFromRequest($key, $values[$key]);
                    }
                } elseif (in_array($key, ['title', 'alt'], true) && array_key_exists($key, $data)) {
                    $element->{$key} = is_string($data[$key]) ? $data[$key] : null;
                }
            }
        } while ($appliedAny);
    }

    /**
     * Finds an element as the editor identifies it. Each is found with its own copy of the request, so the request’s
     * own {@see ElementRequest} isn’t taken to be editing it.
     *
     * @param  array<mixed>  $item
     */
    private function element(Request $request, array $item): ?ElementInterface
    {
        $isValid = Validator::make($item, [
            'elementType' => ['required', 'string'],
            'elementId' => ['required', 'integer'],
            'siteId' => ['nullable', 'integer'],
            'draftId' => ['nullable', 'integer'],
            'ownerId' => ['nullable', 'integer'],
            'fieldId' => ['nullable', 'integer'],
            'namespace' => ['nullable', 'list'],
            'namespace.*' => ['string'],
        ])->passes();

        if (! $isValid) {
            return null;
        }

        $overrides = array_filter([
            'type' => $item['elementType'] ?? null,
            'id' => $item['elementId'] ?? null,
            'draftId' => $item['draftId'] ?? null,
            'siteId' => $item['siteId'] ?? null,
            'ownerId' => $item['ownerId'] ?? null,
            'fieldId' => $item['fieldId'] ?? null,
        ], static fn (mixed $value): bool => $value !== null);

        try {
            $element = ElementRequest::createFrom($request)->element($overrides, checkForProvisionalDraft: true);
        } catch (HttpException) {
            return null;
        }

        return $element instanceof ElementInterface ? $element : null;
    }
}
