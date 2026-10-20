<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Http\Controllers;

use CraftCms\Cms\Ai\AskAi\Assistant;
use CraftCms\Cms\Ai\AskAi\Subject;
use CraftCms\Cms\Ai\AskAi\Target;
use CraftCms\Cms\Ai\Exceptions\AiUnavailableException;
use CraftCms\Cms\Ai\Exceptions\InvalidAiResponseException;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Markdown as MarkdownField;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\FieldLayout\LayoutElements\Assets\AltField;
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
    /**
     * The `context` value that sends every field.
     */
    public const string ALL_CONTEXT = '*';

    public function __construct(
        private ElementRequest $request,
        private Assistant $assistant,
        private FieldLayoutCompiler $compiler,
    ) {}

    /**
     * Returns the prompt form, and the fields that can be chosen as its context.
     */
    public function form(UiResolver $uiResolver): JsonResponse
    {
        $element = $this->element();
        $targets = $this->targets($element);

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
                    'options' => $this->placeholderOptions($targets),
                ]]))
                ->instructions(t('Describe what to write or change, or ask for a review. Type “{” to insert a field’s current value.'))
                ->required(),
        ]);

        if ($this->assistant->hasImage($element)) {
            $ui->add(Field::make(null, Checkbox::make('includeImage')->label(t('Include the image')))
                ->instructions(t('Sends the image to the AI provider, so the model can look at it. Needs a model with vision.'))
                ->instructionsPosition('after'));
        }

        return new JsonResponse([
            'ui' => $uiResolver->resolve($ui, new UiContext(values: [
                'prompt' => '',
                'includeImage' => true,
            ])),
            'contextOptions' => array_values(array_map(static fn (Target $target): array => [
                'value' => $target->path(),
                'label' => $target->label(),
            ], $targets)),
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
            'context' => ['nullable', 'array'],
            'context.*' => ['string'],
            'includeImage' => ['nullable', 'boolean'],
            'namespace' => ['nullable', 'string', 'regex:/^[^.]+(\.[^.]+)*$/'],
        ]);

        $element = $this->element();
        $targets = $this->targets($element);
        $context = $this->request->input('context');

        if (is_array($context) && ! in_array(self::ALL_CONTEXT, $context, true)) {
            $targets = array_filter($targets, static fn (Target $target): bool => in_array($target->path(), $context, true));
        }

        if ($targets === []) {
            abort(400, 'There are no fields to ask about.');
        }

        $copies = [];

        try {
            $answer = $this->assistant->ask(
                $element,
                $targets,
                $this->request->string('prompt')->toString(),
                $this->request->boolean('includeImage'),
            );

            foreach ($answer->values as $id => $value) {
                $target = $targets[$id];
                $copy = $copies[spl_object_id($target->subject)] ??= clone $target->subject->element;
                $this->assistant->apply($copy, $target, $value);
            }
        } catch (AiUnavailableException|InvalidAiResponseException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            report($exception);

            return new JsonResponse(['message' => t('The content couldn’t be generated. Try again, or ask an admin to check the AI connection.')], 502);
        }

        $namespace = $this->request->input('namespace');
        $namespace = is_string($namespace) ? explode('.', $namespace) : [];
        $compiled = [];
        $proposals = [];

        foreach (array_keys($answer->values) as $id) {
            $target = $targets[$id];
            $subject = $target->subject;
            $copy = $copies[spl_object_id($subject)];
            $layout = $subject->element->getFieldLayout();
            ['editable' => $editable, 'current' => $current, 'proposed' => $proposed, 'proposedContext' => $proposedContext] = $compiled[spl_object_id($subject)] ??= [
                'editable' => $this->compiler->compile($layout, $copy, new UiContext(
                    namespace: [...$namespace, ...$subject->path],
                )),
                'current' => $this->compiler->compile($layout, $subject->element, new UiContext(
                    namespace: 'askAiCurrent',
                    mode: ControlMode::ReadOnly,
                )),
                'proposed' => $this->compiler->compile($layout, $copy, new UiContext(
                    namespace: 'askAiProposed',
                    mode: ControlMode::ReadOnly,
                )),
                'proposedContext' => $this->assistant->context($copy),
            ];
            $uid = $target->field->uid;
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

            $currentText = Assistant::text($target->value);
            $proposedText = Assistant::text($proposedContext[$target->key] ?? null);
            $isText = $currentText !== null && $proposedText !== null;

            $proposals[] = [
                'key' => $id,
                'label' => $target->label(),
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

        return new JsonResponse([
            'proposals' => $proposals,
            'summary' => $answer->summary,
            'findings' => array_map(static fn (array $finding): array => [
                'key' => $finding['field'],
                'label' => $finding['field'] !== null ? $targets[$finding['field']]->label() : null,
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
     * Returns the fields of the element, and of the elements nested in it, that the model can see, with the form’s
     * current values set on them.
     *
     * @return array<string, Target>
     */
    private function targets(ElementInterface $element): array
    {
        $values = $this->request->all();

        return $this->assistant->targets($this->assistant->subjects($element, function (Subject $subject) use ($values): void {
            $data = $subject->path === [] ? $values : data_get($values, $subject->path);

            if (is_array($data)) {
                $this->applyFormValues($subject->element, $data);
            }
        }));
    }

    /**
     * Sets the form’s current values on an element, so the model works on changes that haven’t been saved yet.
     *
     * Only the values the model sees are set: the title, alternative text, and custom fields that the user can see and
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

            foreach ($this->assistant->allFields($element) as $key => $layoutField) {
                if (isset($applied[$key]) || $layoutField->uiMode($element) !== ControlMode::Editable) {
                    continue;
                }

                $applied[$key] = $appliedAny = true;

                if ($layoutField instanceof CustomField) {
                    if (! $layoutField->getField() instanceof ElementContainerFieldInterface && array_key_exists($key, $values)) {
                        $element->setFieldValueFromRequest($key, $values[$key]);
                    }
                } elseif (($layoutField instanceof TitleField || $layoutField instanceof AltField) && array_key_exists($key, $data)) {
                    $value = $data[$key];
                    $element->{$key} = is_string($value) ? $value : null;
                }
            }
        } while ($appliedAny);
    }

    /**
     * Returns the element’s own fields, whose values the prompt can reference.
     *
     * @param  array<string, Target>  $targets
     * @return list<array{label: string, value: string, keywords: list<string>}>
     */
    private function placeholderOptions(array $targets): array
    {
        return array_values(array_map(static fn (Target $target): array => [
            'label' => $target->label(),
            'value' => "{{$target->key}}",
            'keywords' => [$target->key],
        ], array_filter($targets, static fn (Target $target): bool => ! $target->subject->isNested())));
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
