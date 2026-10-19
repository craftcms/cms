<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Http\Controllers;

use CraftCms\Cms\Ai\AskAi\Assistant;
use CraftCms\Cms\Ai\Exceptions\AiUnavailableException;
use CraftCms\Cms\Ai\Exceptions\InvalidAiResponseException;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Markdown as MarkdownField;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\FieldLayout\LayoutElements\Assets\AltField;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\TitleField;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Ui\Controls\Checkbox;
use CraftCms\Cms\Ui\Controls\Markdown;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\Nodes\ActionMenu;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;
use Illuminate\Http\JsonResponse;
use Throwable;

use function CraftCms\Cms\t;

/**
 * Handles “Ask AI” in the element editor.
 *
 * @since 6.0.0
 */
readonly class AskAiController
{
    public function __construct(
        private ElementRequest $request,
        private Assistant $assistant,
        private FieldLayoutCompiler $compiler,
    ) {}

    /**
     * Returns the prompt form.
     */
    public function form(UiResolver $uiResolver): JsonResponse
    {
        $this->request->validate([
            'layoutElementUid' => ['nullable', 'string'],
        ]);

        $element = $this->element();
        $this->applyFormValues($element);
        $target = $this->target($element);

        $ui = Ui::make([
            Field::make(t('Prompt'), Markdown::make('prompt')
                ->rows(6)
                ->toolbarButtons([
                    MarkdownField::TOOLBAR_BOLD,
                    MarkdownField::TOOLBAR_ITALIC,
                    MarkdownField::TOOLBAR_CODE,
                    MarkdownField::TOOLBAR_UNORDERED_LIST,
                    MarkdownField::TOOLBAR_ORDERED_LIST,
                ])
                ->textExpanderTriggers([[
                    'trigger' => '{',
                    'boundary' => 'anywhere',
                    'label' => t('Fields'),
                    'options' => $this->placeholderOptions($element),
                ]]))
                ->instructions(t('Describe what to write or change, or ask for a review. Type “{” to insert a field’s current value.'))
                ->required(),
            Field::make(null, Checkbox::make('includeElement')
                ->label(t('Include the whole {type}', ['type' => $element::lowerDisplayName()])))
                ->instructions(t('Sends all of its content with the prompt, not only the fields being written.'))
                ->instructionsPosition('after'),
        ]);

        if ($this->assistant->hasImage($element)) {
            $ui->add(Field::make(null, Checkbox::make('includeImage')->label(t('Include the image')))
                ->instructions(t('Sends the image to the AI provider, so the model can look at it. Needs a model with vision.'))
                ->instructionsPosition('after'));
        }

        return new JsonResponse([
            'ui' => $uiResolver->resolve($ui, new UiContext(values: [
                'prompt' => '',
                'includeElement' => $target === null,
                'includeImage' => true,
            ])),
            'title' => $target === null
                ? t('Ask AI')
                : t('Ask AI about “{field}”', ['field' => $target->label() ?? $target->attribute()]),
            'submitLabel' => t('Ask'),
        ]);
    }

    /**
     * Generates values and returns them as proposals for the editor to review.
     */
    public function ask(): JsonResponse
    {
        $this->request->validate([
            'prompt' => ['required', 'string', 'max:10000'],
            'includeElement' => ['nullable', 'boolean'],
            'includeImage' => ['nullable', 'boolean'],
            'namespace' => ['nullable', 'string', 'regex:/^[^.]+(\.[^.]+)*$/'],
            'layoutElementUid' => ['nullable', 'string'],
        ]);

        $element = $this->element();
        $this->applyFormValues($element);
        $target = $this->target($element);
        $fields = $this->assistant->fields($element);

        if ($target !== null) {
            $fields = array_filter($fields, static fn (BaseField $field): bool => $field->uid === $target->uid);
        }

        if ($fields === []) {
            abort(400, 'There are no fields to generate.');
        }

        $current = $this->compiler->compile($element->getFieldLayout(), $element, new UiContext(
            namespace: 'askAiCurrent',
            mode: ControlMode::ReadOnly,
        ));

        try {
            $answer = $this->assistant->ask(
                $element,
                $fields,
                $this->request->string('prompt')->toString(),
                $this->request->boolean('includeElement'),
                $this->request->boolean('includeImage'),
            );

            $proposedElement = clone $element;
            $this->assistant->apply($proposedElement, $fields, $answer->values);
        } catch (AiUnavailableException|InvalidAiResponseException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            report($exception);

            return new JsonResponse(['message' => t('The content couldn’t be generated. Try again, or ask an admin to check the AI connection.')], 502);
        }

        $editable = $this->compiler->compile($element->getFieldLayout(), $proposedElement, new UiContext(
            namespace: $this->request->input('namespace') ?? [],
        ));
        $proposed = $this->compiler->compile($element->getFieldLayout(), $proposedElement, new UiContext(
            namespace: 'askAiProposed',
            mode: ControlMode::ReadOnly,
        ));
        $currentContext = $this->assistant->context($element);
        $proposedContext = $this->assistant->context($proposedElement);
        $proposals = [];

        foreach (array_keys($answer->values) as $key) {
            $uid = $fields[$key]->uid;
            $node = $this->findNode($editable->nodes, $uid);
            $currentNode = $this->findNode($current->nodes, $uid);
            $proposedNode = $this->findNode($proposed->nodes, $uid);

            if (! $node?->control || ! $currentNode?->control || ! $proposedNode?->control) {
                continue;
            }

            $value = data_get($editable->values, $node->control->path);

            // The model may return a field unchanged rather than null.
            if ($value === data_get($current->values, $currentNode->control->path)) {
                continue;
            }

            $currentText = Assistant::text($currentContext[$key] ?? null);
            $proposedText = Assistant::text($proposedContext[$key] ?? null);
            $isText = $currentText !== null && $proposedText !== null;

            $proposals[] = [
                'layoutElementUid' => $uid,
                'label' => $fields[$key]->label() ?? $key,
                'node' => $node,
                'value' => $value,
                // Text values are shown as a diff; anything else as the field’s own read-only control.
                'diff' => $isText ? ['current' => $currentText, 'proposed' => $proposedText] : null,
                'preview' => $isText ? null : [
                    'current' => $this->payloadFor($current, $currentNode),
                    'proposed' => $this->payloadFor($proposed, $proposedNode),
                ],
            ];
        }

        $allFields = $this->assistant->allFields($element);

        return new JsonResponse([
            'proposals' => $proposals,
            'summary' => $answer->summary,
            'findings' => array_map(static fn (array $finding): array => [
                'layoutElementUid' => $finding['field'] !== null ? $allFields[$finding['field']]->uid : null,
                'label' => $finding['field'] !== null ? ($allFields[$finding['field']]->label() ?? $finding['field']) : null,
                'quote' => $finding['quote'],
                'issue' => $finding['issue'],
            ], $answer->findings),
        ]);
    }

    private function element(): ElementInterface
    {
        $element = $this->request->element(checkForProvisionalDraft: true);

        if (! $element instanceof ElementInterface) {
            abort(400, 'No element was identified by the request.');
        }

        abort_unless($this->assistant->canGenerate($element), 403, t('Ask AI isn’t available for this {type}.', ['type' => $element::lowerDisplayName()]));

        return $element;
    }

    /**
     * Sets the form’s current values on the element, so the model works on changes that haven’t been saved yet.
     *
     * Only the values the model sees are set: the title, alternative text, and custom fields that the user can see and
     * edit. Fields that hold nested elements are left out, as setting their values can create drafts of the nested
     * elements. Fields that a value reveals get their own values on the next pass.
     */
    private function applyFormValues(ElementInterface $element): void
    {
        $values = $this->request->input('fields');
        $values = is_array($values) ? $values : [];
        $applied = [];

        do {
            $appliedAny = false;

            foreach ($this->assistant->allFields($element) as $key => $layoutField) {
                if (isset($applied[$key]) || $layoutField->uiMode($element) !== ControlMode::Editable) {
                    continue;
                }

                $applied[$key] = $appliedAny = true;

                if ($layoutField instanceof CustomField) {
                    if (! $layoutField->getField() instanceof ElementContainerFieldInterface && array_key_exists($key, $values)) {
                        $element->setFieldValueFromRequest($key, $values[$key]);
                    }
                } elseif (($layoutField instanceof TitleField || $layoutField instanceof AltField) && $this->request->has($key)) {
                    $value = $this->request->input($key);
                    $element->{$key} = is_string($value) ? $value : null;
                }
            }
        } while ($appliedAny);
    }

    /**
     * Returns the field a field-level request targets, or `null` for an element-level request.
     */
    private function target(ElementInterface $element): ?BaseField
    {
        $uid = $this->request->input('layoutElementUid');

        if ($uid === null) {
            return null;
        }

        return array_find(
            $this->assistant->fields($element),
            static fn (BaseField $field): bool => $field->uid === $uid,
        ) ?? abort(400, "Invalid layout element UUID: $uid");
    }

    /**
     * Returns the visible fields whose values the prompt can reference.
     *
     * @return list<array{label: string, value: string, keywords: list<string>}>
     */
    private function placeholderOptions(ElementInterface $element): array
    {
        $fields = array_intersect_key($this->assistant->allFields($element), $this->assistant->context($element));

        return array_map(static fn (string $key, BaseField $field): array => [
            'label' => $field->label() ?? $key,
            'value' => "{{$key}}",
            'keywords' => [$key],
        ], array_keys($fields), array_values($fields));
    }

    /**
     * Finds a field’s node, which can be in a tab, or at the top level for fields outside of a tab.
     *
     * @param  list<NodePayload>  $nodes
     */
    private function findNode(array $nodes, string $uid): ?NodePayload
    {
        foreach ($nodes as $node) {
            if (($node->props['layoutUid'] ?? $node->uid) === $uid) {
                return $node;
            }

            if ($found = $this->findNode($node->children ?? [], $uid)) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Returns a read-only payload of a single field, for the review step. The review step labels the field itself, so
     * the field’s own label, status, and action menu are left out.
     */
    private function payloadFor(UiPayload $payload, NodePayload $node): UiPayload
    {
        $node = new NodePayload(
            type: $node->type,
            component: $node->component,
            props: [...$node->props, 'labelSrOnly' => true, 'showStatus' => false],
            uid: $node->uid,
            control: $node->control,
            children: array_values(array_filter(
                $node->children ?? [],
                static fn (NodePayload $child): bool => $child->type !== ActionMenu::class,
            )),
        );

        return new UiPayload(
            scope: $payload->scope,
            refreshable: false,
            nodes: [$node],
            values: $payload->values,
            errors: [],
            globalErrors: [],
        );
    }
}
