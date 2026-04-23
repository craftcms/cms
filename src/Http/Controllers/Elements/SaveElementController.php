<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementActivity;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Enums\ElementActivityType;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\SavesElement;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\Responses\ElementResponse;
use CraftCms\Cms\Site\Sites;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

readonly class SaveElementController
{
    use SavesElement;

    public function __construct(
        protected ElementRequest $request,
        private Drafts $drafts,
        private ElementActivity $elementActivity,
        private Elements $elements,
        private Sites $sites,
    ) {}

    public function store(): Response
    {
        $element = $this->request->element();

        if ($element instanceof Response) {
            return $element;
        }

        if (! $element || $element->getIsDraft() || $element->getIsRevision()) {
            abort(400, 'No element was identified by the request.');
        }

        // Check save permissions before and after applying POST params to the element
        // in case the request was tampered with.
        Gate::authorize('save', $element);

        $this->applyParamsToElement($element);

        Gate::authorize('save', $element);

        if ($element->enabled && $element->getEnabledForSite()) {
            $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
        }

        $isNotNew = $element->id;
        if ($isNotNew) {
            $mutex = Cache::lock("element:$element->id", 15);
            if (! $mutex->get()) {
                abort(500, 'Could not acquire a lock to save the element.');
            }
        }

        if ($element instanceof NestedElementInterface && property_exists($element, 'updateSearchIndexForOwner')) {
            $element->updateSearchIndexForOwner = true;
        }

        try {
            $namespace = $this->request->header('X-Craft-Namespace');
            // crossSiteValidate only if it's multisite, element supports drafts and we're not in a slideout
            $success = $this->elements->saveElement(
                $element,
                crossSiteValidate: (
                    $namespace === null
                    && $this->sites->isMultiSite()
                    && Gate::check('createDrafts', $element)
                ),
            );
        } catch (UnsupportedSiteException $e) {
            $element->errors()->add('siteId', $e->getMessage());
            $success = false;
        } finally {
            if ($isNotNew) {
                $mutex->release();
            }
        }

        if (! $success) {
            return new ElementResponse()->failure($element, mb_ucfirst(t('Couldn’t save {type}.', [
                'type' => $element::lowerDisplayName(),
            ])));
        }

        $this->elementActivity->trackActivity($element, ElementActivityType::Save);

        // See if the user happens to have a provisional element. If so delete it.
        $provisional = $element::find()
            ->provisionalDrafts()
            ->draftOf($element->id)
            ->draftCreator($this->request->user())
            ->siteId($element->siteId)
            ->status(null)
            ->one();

        if ($provisional) {
            $this->elements->deleteElement($provisional, true);
        }

        if (! $this->request->acceptsJson()) {
            // Tell all browser windows about the element save
            session()->broadcastToJs([
                'event' => 'saveElement',
                'id' => $element->id,
            ]);
        }

        return new ElementResponse()->success($element, t('{type} saved.', [
            'type' => $element::displayName(),
        ]), supportsAddAnother: true);
    }

    public function storeForDerivative(): Response
    {
        if (! $this->request->has('newOwnerId')) {
            abort(400, 'No new owner was identified by the request.');
        }

        $element = $this->request->element();

        if (
            ! $element instanceof NestedElementInterface ||
            ! $element->getOwnerId() ||
            ! $element->getIsDraft() ||
            $element->getIsCanonical()
        ) {
            abort(400, 'No element was identified by the request.');
        }

        // Check save permissions before and after applying POST params to the element
        // in case the request was tampered with.
        Gate::authorize('save', $element);

        // Get the new owner and make sure it's a derivative element,
        // and that its canonical element is the nested element's primary owner
        $owner = $this->elements->getElementById($this->request->integer('newOwnerId'), siteId: $element->siteId);

        if ($owner->getIsCanonical()) {
            abort(400, 'The owner element must be a derivative.');
        }

        if ($owner->getCanonicalId() !== $element->getPrimaryOwnerId()) {
            // the owner might be a derivative of another canonical element
            $canonicalOwner = $owner->getCanonical();
            if ($canonicalOwner->getCanonicalId() !== $element->getPrimaryOwnerId()) {
                abort(400, 'The canonical owner element must be the primary owner of the nested element.');
            }
        }

        Gate::authorize('save', $owner);

        // Get the old sort order
        $sortOrder = DB::table(Table::ELEMENTS_OWNERS)
            ->where('elementId', $element->id)
            ->where('ownerId', $element->getOwnerId())
            ->value('sortOrder');

        $element->setSortOrder($sortOrder);

        DB::beginTransaction();

        try {
            // Remove existing ownership data for the element within the canonical owner,
            // and for its canonical element within the derivative
            DB::table(Table::ELEMENTS_OWNERS)
                ->where('elementId', $element->id)
                ->where('ownerId', $owner->getCanonicalId())
                ->orWhere(fn (Builder $query) => $query
                    ->where('elementId', $element->getCanonicalId())
                    ->where('ownerId', $owner->id)
                )
                ->delete();

            // Remove existing ownership data for the element within the canonical owner
            DB::table(Table::ELEMENTS_OWNERS)
                ->where('elementId', $element->id)
                ->where('ownerId', $owner->getCanonicalId())
                ->delete();

            // Remove the draft data, but preserve the canonicalId
            $element->setPrimaryOwner($owner);
            $element->setOwner($owner);

            $this->elements->saveElement($element);

            $this->applyParamsToElement($element);

            Gate::authorize('save', $element);

            if ($element->enabled && $element->getEnabledForSite()) {
                $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
            }

            try {
                $success = $this->elements->saveElement($element);
            } catch (UnsupportedSiteException $e) {
                $element->errors()->add('siteId', $e->getMessage());
                $success = false;
            }

            if (! $success) {
                DB::rollBack();

                return new ElementResponse()->failure($element, mb_ucfirst(t('Couldn’t save {type}.', [
                    'type' => $element::lowerDisplayName(),
                ])));
            }

            if ($element->getIsDraft()) {
                $this->drafts->removeDraftData($element);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return new ElementResponse()->success($element, t('{type} saved.', [
            'type' => $element::displayName(),
        ]));
    }
}
