<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Http\Controllers;

use CraftCms\Cms\Ai\AskAi\Answer;
use CraftCms\Cms\Ai\AskAi\Assistant;
use CraftCms\Cms\Ai\AskAi\CompiledSubject;
use CraftCms\Cms\Ai\AskAi\Data\ContextOption;
use CraftCms\Cms\Ai\AskAi\Data\Finding;
use CraftCms\Cms\Ai\AskAi\Data\Proposal;
use CraftCms\Cms\Ai\AskAi\Data\Result;
use CraftCms\Cms\Ai\AskAi\Data\Turn;
use CraftCms\Cms\Ai\AskAi\Subject;
use CraftCms\Cms\Ai\AskAi\Target;
use CraftCms\Cms\Ai\Exceptions\AiUnavailableException;
use CraftCms\Cms\Ai\Exceptions\InvalidAiResponseException;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\FieldLayout\LayoutElements\Assets\AltField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\TitleField;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Support\Json;
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
use Generator;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
                ->rows(4)
                ->placeholder(t('Describe what to write or change, or ask for a review. Type { to insert a field’s current value.'))
                ->showToolbar(false)
                ->textExpanderTriggers([[
                    'trigger' => '{',
                    'boundary' => 'anywhere',
                    'label' => t('Fields'),
                    'options' => $this->placeholderOptions($targets),
                ]]))
                // The panel and its Ask button say what the field is for, and the placeholder shows the instructions.
                ->labelSrOnly()
                ->instructions(t('Describe what to write or change, or ask for a review. Type <kbd>{</kbd> to insert a field’s current value.'))
                ->instructionsSrOnly()
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
            'contextOptions' => array_values(array_map(
                static fn (Target $target): ContextOption => new ContextOption($target->path(), $target->label()),
                $targets,
            )),
            'submitLabel' => t('Ask'),
        ]);
    }

    /**
     * Generates values and streams them back as proposals for the editor to review.
     *
     * The response is newline-delimited JSON events: `summary` events with each piece of the model’s summary as it’s
     * written, then a `result` event with the proposals, findings, and the turn, or an `error` event with the status the
     * request would have failed with. Invalid requests fail before streaming, with a regular JSON error response.
     *
     * The conversation isn’t stored: the panel sends its earlier turns back, as each `result`’s `turn` returns them.
     */
    public function ask(): StreamedResponse
    {
        $this->request->validate([
            'prompt' => ['required', 'string', 'max:10000'],
            'history' => ['nullable', 'array'],
            'history.*' => ['array:prompt,answer'],
            // Resolved prompts, as each `result` returned them, which can be longer than a prompt, or empty.
            'history.*.prompt' => ['present', 'nullable', 'string'],
            'history.*.answer' => ['required', 'string'],
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

        // Resolved once, for the model and for the turn returned for later requests, so the turn reads the same in
        // them even after the fields it references change.
        $prompt = $this->assistant->resolvePrompt($element, $this->request->string('prompt')->toString());

        // A generator function, so Laravel flushes each event as it's yielded, and tells proxies such as nginx not to
        // buffer them. An arrow function returning a generator wouldn't count as one.
        return response()->stream(function () use ($element, $targets, $prompt): Generator {
            yield from $this->streamAnswer($element, $targets, $prompt);
        }, headers: [
            'Content-Type' => 'application/x-ndjson',
            'Cache-Control' => 'no-cache',
        ]);
    }

    /**
     * Prompts the model and yields its answer as events, one line of JSON each.
     *
     * Errors are events too, as the response has started by the time they happen.
     *
     * @param  array<string, Target>  $targets
     * @return Generator<int, string>
     */
    private function streamAnswer(ElementInterface $element, array $targets, string $prompt): Generator
    {
        $copies = [];
        $values = [];

        try {
            $reply = $this->assistant->ask(
                $element,
                $targets,
                $prompt,
                $this->request->boolean('includeImage'),
                array_values($this->request->array('history')),
            );

            foreach ($reply as $delta) {
                yield $this->event(['type' => 'summary', 'delta' => $delta]);
            }

            $answer = $reply->getReturn();
            $findings = $answer->findings;

            // A value that isn’t valid for its field is left out, with a finding saying so, rather than failing the rest.
            foreach ($answer->values as $id => $value) {
                $target = $targets[$id];
                $copy = $copies[spl_object_id($target->subject)] ??= clone $target->subject->element;

                try {
                    $this->assistant->apply($copy, $target, $value);
                    $values[$id] = $value;
                } catch (InvalidAiResponseException) {
                    $findings[] = Finding::about($target, null, t('The generated value isn’t valid for this field, so it was left out.'));
                }
            }

            // With only the values that were kept, and the findings about those left out, as the editor sees them.
            $kept = new Answer($values, $answer->summary, $findings);

            yield $this->event(['type' => 'result', 'result' => $this->result($targets, $prompt, $kept, $copies)]);
        } catch (AiUnavailableException|InvalidAiResponseException $exception) {
            yield $this->event(['type' => 'error', 'status' => 422, 'message' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);

            // A long conversation can outgrow what the model accepts, which trying again won’t fix.
            yield $this->event(['type' => 'error', 'status' => 502, 'message' => $this->request->array('history') !== []
                ? t('The content couldn’t be generated. Try again, start a new conversation, or ask an admin to check the AI connection.')
                : t('The content couldn’t be generated. Try again, or ask an admin to check the AI connection.')]);
        }
    }

    /**
     * Encodes an event as a line of JSON.
     *
     * Throws rather than returning an empty line, so a value that can't be encoded is reported.
     *
     * @param  array<string, mixed>  $event
     */
    private function event(array $event): string
    {
        return Json::encode($event, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
    }

    /**
     * Returns the answer as proposals for the fields whose values changed, with the findings, and the turn to send back
     * with follow-ups.
     *
     * @param  array<string, Target>  $targets
     * @param  Answer  $answer  The answer, with only the values that are valid for their fields.
     * @param  array<int, ElementInterface>  $copies  Copies of the subjects’ elements with the values set, by subject object ID.
     */
    private function result(array $targets, string $prompt, Answer $answer, array $copies): Result
    {
        $namespace = $this->request->input('namespace');
        $namespace = is_string($namespace) ? explode('.', $namespace) : [];
        $compiled = [];
        $proposals = [];

        foreach (array_keys($answer->values) as $id) {
            $subject = $targets[$id]->subject;
            $proposal = $this->proposal($id, $targets[$id], $compiled[spl_object_id($subject)] ??= $this->compile($subject, $copies[spl_object_id($subject)], $namespace));

            if ($proposal) {
                $proposals[] = $proposal;
            }
        }

        return new Result(
            proposals: $proposals,
            summary: $answer->summary,
            findings: $answer->findings,
            turn: new Turn($prompt, $answer->toJson()),
        );
    }

    /**
     * Compiles a subject’s field layout with the proposed values set.
     *
     * @param  list<string>  $namespace  The namespace of the editor’s form.
     */
    private function compile(Subject $subject, ElementInterface $copy, array $namespace): CompiledSubject
    {
        $layout = $subject->element->getFieldLayout();

        return new CompiledSubject(
            editable: $this->compiler->compile($layout, $copy, new UiContext(
                namespace: [...$namespace, ...$subject->path],
            )),
            current: $this->compiler->compile($layout, $subject->element, new UiContext(
                namespace: 'askAiCurrent',
                mode: ControlMode::ReadOnly,
            )),
            proposed: $this->compiler->compile($layout, $copy, new UiContext(
                namespace: 'askAiProposed',
                mode: ControlMode::ReadOnly,
            )),
            proposedContext: $this->assistant->context($copy),
        );
    }

    /**
     * Returns the proposal for a field, or `null` if its field isn’t in the form, or its value didn’t change.
     */
    private function proposal(string $id, Target $target, CompiledSubject $compiled): ?Proposal
    {
        $uid = $target->field->uid;
        $node = $this->findNode($compiled->editable->nodes, $uid);
        $currentNode = $this->findNode($compiled->current->nodes, $uid);
        $proposedNode = $this->findNode($compiled->proposed->nodes, $uid);

        if (! $node?->control || ! $currentNode?->control || ! $proposedNode?->control) {
            return null;
        }

        $value = data_get($compiled->editable->values, $node->control->path);

        // The model may return a field unchanged rather than null.
        if ($value === data_get($compiled->current->values, $currentNode->control->path)) {
            return null;
        }

        $currentText = Assistant::text($target->value);
        $proposedText = Assistant::text($compiled->proposedContext[$target->key] ?? null);
        $isText = $currentText !== null && $proposedText !== null;

        return new Proposal(
            key: $id,
            label: $target->label(),
            node: $node,
            value: $value,
            // Text values are shown as a diff; anything else as the field’s own read-only control.
            diff: $isText ? ['current' => $currentText, 'proposed' => $proposedText] : null,
            preview: $isText ? null : [
                'current' => $this->payloadFor($compiled->current, $currentNode),
                'proposed' => $this->payloadFor($compiled->proposed, $proposedNode),
            ],
        );
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
