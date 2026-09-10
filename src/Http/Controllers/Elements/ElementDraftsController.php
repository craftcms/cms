<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementActivity;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Enums\ElementActivityType;
use CraftCms\Cms\Element\Events\DraftCreated;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\EditsElement;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\SavesElement;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\UpdatesFieldLayout;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\ElementResponse;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

class ElementDraftsController
{
    use EditsElement;
    use RespondsWithFlash;
    use SavesElement;
    use UpdatesFieldLayout;

    public function __construct(
        protected readonly ElementRequest $request,
        private readonly Drafts $drafts,
        private readonly Elements $elements,
        private readonly ElementActivity $elementActivity,
    ) {}

    public function store(): Response
    {
        $element = $this->request->element();

        // this can happen if we're creating e.g. nested entry in a matrix field (cards or element index)
        // and we hit "create entry" before the autosave kicks in
        if ($element instanceof Response) {
            return $element;
        }

        if (! $element || $element->getIsRevision()) {
            abort(400, 'No element was identified by the request.');
        }

        $provisional = $this->request->boolean('provisional');

        if (! $element->getIsDraft() && ! $provisional) {
            Gate::authorize('createDrafts', $element);
        } elseif (! $this->canSave($element, $this->request->craftUser())) {
            abort(403, 'User not authorized to save this element.');
        }

        if (! $element->getIsDraft() && $provisional) {
            // Make sure a provisional draft doesn't already exist for this element/user combo
            $existingProvisionalDraft = $element::find()
                ->provisionalDrafts()
                ->draftOf($element->id)
                ->draftCreator($this->request->craftUser()?->getCraftUserId())
                ->site('*')
                ->status(null)
                ->one();

            if ($existingProvisionalDraft) {
                Log::warning("Overwriting an existing provisional draft for element/user $element->id/{$this->request->craftUser()?->getCraftUserId()}", [__METHOD__]);

                $this->elements->deleteElement($existingProvisionalDraft, true);
            }
        }

        // Keep track of all newly-created draft IDs
        $draftElementIds = [];
        $draftElementUids = [];

        Event::listen(function (DraftCreated $event) use (&$draftElementIds, &$draftElementUids) {
            $draftElementIds[$event->canonical->id] = $event->draft->id;
            $draftElementUids[$event->canonical->uid] = $event->draft->uid;
        });

        DB::beginTransaction();

        try {
            // Are we creating the draft here?
            if (! $element->getIsDraft()) {
                /** @var Element $element */
                $draft = $this->drafts->createDraft(
                    canonical: $element,
                    creatorId: $this->request->craftUser()?->getCraftUserId(),
                    provisional: $provisional,
                );

                $draft->setCanonical($element);

                $element = $draft;
            }

            // keep track of the original field layout ID, in case it changes here
            $oldFieldLayoutId = $element->getFieldLayout()?->id;

            $this->applyParamsToElement($element);

            // Make sure nothing just changed that would prevent the user from saving
            if (! $this->canSave($element, $this->request->craftUser())) {
                abort(403, 'User not authorized to save this element.');
            }

            if ($this->request->boolean('dropProvisional')) {
                $element->isProvisionalDraft = false;
            }

            $element->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);

            // If the field layout ID changed, save all content
            $saveContent = $element->getFieldLayout()?->id !== $oldFieldLayoutId;

            if (! $this->elements->saveElement($element, saveContent: $saveContent)) {
                DB::rollBack();

                return new ElementResponse()->failure($element, mb_ucfirst(t('Couldn’t save {type}.', [
                    'type' => t('draft'),
                ])));
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->elementActivity->trackActivity($element, ElementActivityType::Save);

        $data = [
            'canonicalId' => $element->getCanonicalId(),
            'elementId' => $element->id,
            'draftId' => $element->draftId,
            'timestamp' => I18N::getFormatter()->asTimestamp($element->dateUpdated, 'short', true),
            'creator' => $element->getDraftCreator()?->getName(),
            'draftName' => $element->draftName,
            'draftNotes' => $element->draftNotes,
            'modifiedAttributes' => $element->getModifiedAttributes(),
            'draftElementIds' => $draftElementIds,
            'draftElementUids' => $draftElementUids,
        ];

        if ($this->request->isCpRequest()) {
            [$docTitle, $title] = $this->editElementTitles($element);
            $previewTargets = $element->getPreviewTargets();
            // Compiled once and shared: the edit screen payload adopts it as
            // its own, and `fieldLayoutData()` scopes it for the response.
            $form = $this->compileFieldLayout($element);
            // Built before `fieldLayoutData()` drains the HTML stack, so
            // whatever the sidebar and metadata register still lands in
            // `headHtml`/`bodyHtml`.
            $screen = $this->editScreenData($element, $form);
            $data += $this->fieldLayoutData($element, $form);
            $data += [
                'docTitle' => $docTitle,
                'title' => $title,
                'previewTargets' => $previewTargets,
                'previewParamValue' => $previewTargets ? Crypt::encrypt(Str::random(10)) : null,
                'deltaNames' => DeltaRegistry::getNames(),
                'initialDeltaValues' => DeltaRegistry::getInitialValues(),
                'updatedTimestamp' => $element->dateUpdated->getTimestamp(),
                'canonicalUpdatedTimestamp' => $element->getCanonical()->dateUpdated->getTimestamp(),
                'screen' => $screen,
            ];
        }

        // Make sure the user is authorized to preview the draft
        SessionAuth::authorize("previewDraft:$element->draftId");

        return new ElementResponse()->success($element, t('{type} saved.', [
            'type' => t('Draft'),
        ]), $data, true);
    }

    /**
     * The element edit screen's payload, as a fresh page load would render it.
     *
     * Autosaving turns a canonical element into a provisional draft partway
     * through editing, and that changes the screen around the form: the
     * “Showing your unsaved changes” notice appears, so does the Discard
     * changes button, the Save button starts applying a draft, and the drafts
     * menu gains an entry. Rebuilding the screen's own view model is what lets
     * the client adopt all of that without a page load, instead of inferring it
     * from the handful of loose keys the response has always carried.
     *
     * Nested under its own key rather than merged in: the legacy element editor
     * reads this response too, and several of its keys (`form`,
     * `previewTargets`) mean something different there.
     *
     * `form` is dropped — the compiled layout is already on the response at the
     * top level, scoped to whatever the request asked for, and shipping it
     * twice would double the size of every keystroke's autosave.
     *
     * @return array<string, mixed>|null
     */
    private function editScreenData(ElementInterface $element, ?FormPayload $form): ?array
    {
        $viewModel = $element::editViewModelClass();

        if ($viewModel === null) {
            return null;
        }

        // Saving got this far, so the user can save this element.
        $data = new $viewModel($element, $this->request, true)
            ->withForm($form)
            ->toArray();

        unset($data['form']);

        return $data;
    }

    public function ensure(): Response
    {
        $element = $this->request->element(checkForProvisionalDraft: true);

        if (! $element || $element->getIsRevision()) {
            abort(400, 'No element was identified by the request.');
        }

        if ($element->getIsDraft()) {
            return $this->asSuccess(data: [
                'elementId' => $element->id,
            ]);
        }

        Gate::authorize('createDrafts', $element);

        // Make sure a provisional draft doesn't already exist for this element/user combo
        $provisionalId = $element::find()
            ->provisionalDrafts()
            ->draftOf($element->id)
            ->draftCreator($this->request->craftUser()?->getCraftUserId())
            ->site('*')
            ->status(null)
            ->ids()[0] ?? null;

        if ($provisionalId) {
            return $this->asSuccess(data: [
                'elementId' => $provisionalId,
            ]);
        }

        $draft = $this->drafts->createDraft(
            canonical: $element,
            creatorId: $this->request->craftUser()?->getCraftUserId(),
            provisional: true,
        );

        return $this->asSuccess(data: [
            'elementId' => $draft->id,
        ]);
    }

    public function apply(): Response
    {
        $element = $this->request->element();

        // this can happen if creating element via slideout, and we hit "create entry" before the autosave kicks in
        if ($element instanceof Response) {
            return $element;
        }

        if (! $element || ! $element->getIsDraft()) {
            abort(400, 'No draft was identified by the request.');
        }

        // keep track of the original field layout ID, in case it changes here
        $oldFieldLayoutId = $element->getFieldLayout()?->id;

        $this->applyParamsToElement($element);

        Gate::authorize('save', $element);

        $isUnpublishedDraft = $element->getIsUnpublishedDraft();

        if (! Gate::check('saveCanonical', $element)) {
            abort(403, $isUnpublishedDraft
                ? 'User not authorized to create this element.'
                : 'User not authorized to save this element.'
            );
        }

        // Validate and save the draft
        if ($element->enabled && $element->getEnabledForSite()) {
            $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
        }

        // if we're about to apply an unpublished draft, set propagateRequired to true
        if ($isUnpublishedDraft) {
            $element->propagateRequired = true;
        }

        $element->applyingDraft = true;

        // If the field layout ID changed, save all content
        $saveContent = $element->getFieldLayout()?->id !== $oldFieldLayoutId;

        $namespace = $this->request->header('X-Craft-Namespace');
        $crossSiteValidate = $namespace === null && Sites::isMultiSite();

        if (! $this->elements->saveElement(
            element: $element,
            crossSiteValidate: $crossSiteValidate,
            saveContent: $saveContent,
        )) {
            // save the draft anyway, so we don’t lose the latest changes
            // (see https://github.com/craftcms/cms/issues/18657)
            /** @var MessageBag $errors */
            $errors = $element->errors();
            $invalidNestedElementIds = $element->getInvalidNestedElementIds();
            $element->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
            $this->elements->saveElement(element: $element, runValidation: false, saveContent: $saveContent);
            $element->clearErrors();
            $element->errors()->merge($errors);
            $element->addInvalidNestedElementIds($invalidNestedElementIds);

            return new ElementResponse()->applyDraftFailure($element);
        }

        $element->applyingDraft = false;

        if (! $isUnpublishedDraft) {
            $mutex = Cache::lock("element:$element->canonicalId", 15);
            if (! $mutex->get()) {
                abort(500, 'Could not acquire a lock to save the element.');
            }
        }

        $attributes = [];

        if ($element instanceof NestedElementInterface) {
            $attributes['updateSearchIndexForOwner'] = true;
        }

        try {
            $element->propagateRequired = false;
            $canonical = $this->drafts->applyDraft($element, $attributes);
        } catch (InvalidElementException) {
            return new ElementResponse()->applyDraftFailure($element);
        } finally {
            if (! $isUnpublishedDraft) {
                $mutex->release();
            }
        }

        $this->elementActivity->trackActivity($canonical, ElementActivityType::Save);

        if (! $this->request->expectsJson()) {
            // Tell all browser windows about the element save
            session()->broadcastToJs([
                'event' => 'saveElement',
                'id' => $canonical->id,
            ]);

            if (! $isUnpublishedDraft) {
                session()->broadcastToJs([
                    'event' => 'deleteDraft',
                    'canonicalId' => $element->getCanonicalId(),
                    'draftId' => $element->draftId,
                ]);
            }
        }

        $message = match (true) {
            $isUnpublishedDraft => t('{type} created.', [
                'type' => $element::displayName(),
            ]),
            $element->isProvisionalDraft => t('{type} saved.', [
                'type' => $element::displayName(),
            ]),
            default => t('Draft applied.'),
        };

        return new ElementResponse()->success($canonical, $message, supportsAddAnother: true);
    }

    public function destroy(): Response
    {
        $element = $this->request->element();

        if ($element instanceof Response) {
            return $element;
        }

        if (! $element || ! $element->getIsDraft()) {
            abort(400, 'No draft was identified by the request.');
        }

        Gate::authorize('delete', $element);

        if (! $this->drafts->discardDraft($element)) {
            return new ElementResponse()->failure($element, t('Couldn’t delete {type}.', [
                'type' => t('draft'),
            ]));
        }

        $message = $element->isProvisionalDraft
            ? t('Changes discarded.')
            : t('{type} deleted.', [
                'type' => t('Draft'),
            ]);

        if (! $this->request->acceptsJson()) {
            // Tell all browser windows about the draft deletion
            session()->broadcastToJs([
                'event' => 'deleteDraft',
                'canonicalId' => $element->getCanonicalId(),
                'draftId' => $element->draftId,
            ]);
        }

        return new ElementResponse()->success($element, $message);
    }
}
