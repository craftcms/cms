<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementActivity;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Enums\ElementActivityType;
use CraftCms\Cms\Element\Events\DraftCreated;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\EditsElement;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\SavesElement;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\UpdatesFieldLayout;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\ElementResponse;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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
        } elseif (! $this->canSave($element, $this->request->user())) {
            abort(403, 'User not authorized to save this element.');
        }

        if (! $element->getIsDraft() && $provisional) {
            // Make sure a provisional draft doesn't already exist for this element/user combo
            $existingProvisionalDraft = $element::find()
                ->provisionalDrafts()
                ->draftOf($element->id)
                ->draftCreator($this->request->user()->id)
                ->site('*')
                ->status(null)
                ->one();

            if ($existingProvisionalDraft) {
                Log::warning("Overwriting an existing provisional draft for element/user $element->id/{$this->request->user()->id}", [__METHOD__]);

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
                    creatorId: $this->request->user()->id,
                    provisional: $provisional,
                );

                $draft->setCanonical($element);

                $element = $draft;
            }

            // keep track of the original field layout ID, in case it changes here
            $oldFieldLayoutId = $element->getFieldLayout()?->id;

            $this->applyParamsToElement($element);

            // Make sure nothing just changed that would prevent the user from saving
            if (! $this->canSave($element, $this->request->user())) {
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
            $data += $this->fieldLayoutData($element, [
                'registerDeltas' => true,
            ]);
            $data += [
                'docTitle' => $docTitle,
                'title' => $title,
                'previewTargets' => $previewTargets,
                'previewParamValue' => $previewTargets ? Crypt::encrypt(Str::random(10)) : null,
                'deltaNames' => DeltaRegistry::getNames(),
                'initialDeltaValues' => DeltaRegistry::getInitialValues(),
                'updatedTimestamp' => $element->dateUpdated->getTimestamp(),
                'canonicalUpdatedTimestamp' => $element->getCanonical()->dateUpdated->getTimestamp(),
            ];
        }

        // Make sure the user is authorized to preview the draft
        SessionAuth::authorize("previewDraft:$element->draftId");

        return new ElementResponse()->success($element, t('{type} saved.', [
            'type' => t('Draft'),
        ]), $data, true);
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
            ->draftCreator($this->request->user()->id)
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
            creatorId: $this->request->user()->id,
            provisional: true,
        );

        return $this->asSuccess(data: [
            'elementId' => $draft->id,
        ]);
    }

    public function apply() {}

    public function destroy() {}
}
