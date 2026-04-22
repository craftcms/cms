<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Exceptions\InvalidTypeException;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\Contracts\NestedElementQueryInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class ElementRequest extends FormRequest
{
    private array $overrides = [];

    private bool $checkForProvisionalDraft = false;

    private bool $strictSite = true;

    /**
     * @var class-string<ElementInterface>
     */
    private string $elementType;

    public function rules(): array
    {
        $fieldsLocation = $this->input('fieldsLocation', 'fields');

        return [
            '*' => [],
            'id' => ['missing'],
            'canonicalId' => ['missing'],

            /**
             * These need to be excluded from the ->validated() call
             * which is passed to setAttributesFromRequest.
             */
            'elementType' => ['exclude'],
            'elementId' => ['exclude'],
            'elementUid' => ['exclude'],
            'draftId' => ['exclude'],
            'revisionId' => ['exclude'],
            'fieldId' => ['exclude'],
            'ownerId' => ['exclude'],
            'newOwnerId' => ['exclude'],
            'siteId' => ['exclude'],
            'enabled' => ['exclude'],
            'setEnabled' => ['exclude'],
            'enabledForSite' => ['exclude'],
            'slug' => ['exclude'],
            'fresh' => ['exclude'],
            'draftName' => ['exclude'],
            'notes' => ['exclude'],
            'fieldsLocation' => ['exclude'],
            'provisional' => ['exclude'],
            'dropProvisional' => ['exclude'],
            'addAnother' => ['exclude'],
            'visibleLayoutElements' => ['exclude'],
            'staticLayoutElements' => ['exclude'],
            'selectedTab' => ['exclude'],
            'applyParams' => ['exclude'],
            'prevalidate' => ['exclude'],
            'asUnpublishedDraft' => ['exclude'],
            'deleteProvisionalDraft' => ['exclude'],
            'updateSearchIndexImmediately' => ['exclude'],
            'failMessage' => ['exclude'],
            'redirect' => ['exclude'],
            'successMessage' => ['exclude'],
            $fieldsLocation => ['exclude'],
        ];
    }

    public function element(array $overrides = [], bool $checkForProvisionalDraft = false, bool $strictSite = true): ElementInterface|Response|null
    {
        $this->overrides = $overrides;
        $this->checkForProvisionalDraft = $checkForProvisionalDraft;
        $this->strictSite = $strictSite;
        $this->elementType = $this->elementType();

        $this->validateElementType($this->elementType);

        $elementId = Arr::get($overrides, 'id', $this->input('elementId'));
        $elementUid = Arr::get($overrides, 'uid', $this->input('elementUid'));
        $draftId = Arr::get($overrides, 'draftId', $this->input('draftId'));
        $revisionId = Arr::get($overrides, 'revisionId', $this->input('revisionId'));

        [$siteId, $preferSites] = $this->site();

        $element = match (true) {
            $draftId || $revisionId => $this->elementByDraftOrRevision($draftId, $revisionId),
            ! is_null($elementId) => $this->elementById(),
            ! is_null($elementUid) => $this->elementByUid(),
            default => null,
        };

        if (is_null($element)) {
            return null;
        }

        abort_unless($this->user()->can('view', $element), 403, 'User not authorized to view this element.');

        if (
            ! $this->strictSite &&
            $element->siteId !== $siteId &&
            ! $this->wantsJson()
        ) {
            return redirect($element->getCpEditUrl());
        }

        return $element;
    }

    /**
     * @return class-string<ElementInterface>
     */
    public function elementType(): string
    {
        $elementType = Arr::get($this->overrides, 'type', $this->input('elementType'));
        $elementId = Arr::get($this->overrides, 'id', $this->input('elementId'));
        $elementUid = Arr::get($this->overrides, 'uid', $this->input('elementUid'));

        if ($elementType) {
            return $this->elementType = $elementType;
        }

        if ($elementId) {
            abort_unless(
                $elementType = Elements::getElementTypeById($elementId),
                400,
                "Invalid element ID: $elementId",
            );

            return $this->elementType = $elementType;
        }

        if ($elementUid) {
            abort_unless(
                $elementType = Elements::getElementTypeByUid($elementUid),
                400,
                "Invalid element UUID: $elementUid",
            );

            return $this->elementType = $elementType;
        }

        abort(400, 'Request missing required param.');
    }

    private function elementQuery(): ElementQueryInterface
    {
        $query = $this->elementType::find();

        if ($query instanceof NestedElementQueryInterface) {
            $fieldId = Arr::get($this->overrides, 'fieldId', $this->input('fieldId'));
            $ownerId = Arr::get($this->overrides, 'ownerId', $this->input('ownerId'));

            $query
                ->fieldId($fieldId)
                ->ownerId($ownerId);
        }

        return $query;
    }

    public function validateElementType(string $elementType): void
    {
        if (ComponentHelper::validateComponentClass($elementType, ElementInterface::class)) {
            return;
        }

        abort(400, new InvalidTypeException($elementType, ElementInterface::class)->getMessage());
    }

    /**
     * @return array{0: int|int[]|null, 1: int[]|null}
     */
    public function site(): array
    {
        if (! $this->elementType::isLocalized()) {
            return [null, null];
        }

        $siteId = Arr::get($this->overrides, 'siteId', $this->input('siteId'));

        if ($siteId) {
            $site = Sites::getSiteById($siteId, true);

            abort_if(is_null($site), 400, "Invalid site ID: $siteId");

            if (Sites::isMultiSite() && ! $this->user()->can("editSite:$site->uid")) {
                abort(403, 'User not authorized to edit content for this site.');
            }
        } else {
            $site = app(RequestedSite::class)->get();

            abort_if(is_null($site), 400, 'User not authorized to edit content in any sites.');
        }

        if ($this->strictSite) {
            return [$site->id, null];
        }

        return [
            Sites::getEditableSiteIds()->all(),
            [$site->id],
        ];
    }

    private function elementByDraftOrRevision(mixed $draftId, mixed $revisionId): ElementInterface|Response
    {
        $provisional = Arr::get($this->overrides, 'isProvisionalDraft', $this->input('provisional'));
        [$siteId, $preferSites] = $this->site();

        $query = $this->elementQuery()
            ->draftId($draftId ? (int) $draftId : null)
            ->revisionId($revisionId ? (int) $revisionId : null)
            ->provisionalDrafts($provisional)
            ->siteId($siteId)
            ->preferSites($preferSites)
            ->unique()
            ->status(null);

        if ($revisionId) {
            $query->trashed(null);
        }

        $element = $query->first();

        if (! $element) {
            // check for the canonical element as a fallback
            $element = $this->elementById() ?? $this->elementByUid();

            if ($element && $this->user()->can('view', $element)) {
                if (! $this->wantsJson()) {
                    return redirect($element->getCpEditUrl());
                }

                return $element;
            }
        }

        if ($element) {
            return $element;
        }

        abort(400, $draftId ? "Invalid draft ID: $draftId" : "Invalid revision ID: $revisionId");
    }

    private function elementById(): ?ElementInterface
    {
        $elementId = Arr::get($this->overrides, 'id', $this->input('elementId'));

        if (! $elementId) {
            return null;
        }

        [$siteId, $preferSites] = $this->site();

        // First check for a provisional draft, if we're open to it
        if ($this->checkForProvisionalDraft) {
            $element = $this->elementQuery()
                ->provisionalDrafts()
                ->draftOf($elementId)
                ->draftCreator($this->user())
                ->siteId($siteId)
                ->preferSites($preferSites)
                ->unique()
                ->status(null)
                ->one();

            if ($element && $this->canSave($element, $this->user())) {
                return $element;
            }
        }

        $element = $this->elementQuery()
            ->id($elementId)
            ->siteId($siteId)
            ->preferSites($preferSites)
            ->unique()
            ->drafts(null)
            ->provisionalDrafts(null)
            ->revisions(null)
            ->status(null)
            ->one();

        if ($element) {
            return $element;
        }

        // finally, check for an unpublished draft
        // (see https://github.com/craftcms/cms/issues/14199)
        return $this->elementQuery()
            ->id($elementId)
            ->siteId($siteId)
            ->preferSites($preferSites)
            ->unique()
            ->draftOf(false)
            ->status(null)
            ->one();
    }

    private function elementByUid(): ?ElementInterface
    {
        $elementUid = Arr::get($this->overrides, 'uid', $this->input('elementUid'));

        if (! $elementUid) {
            return null;
        }

        [$siteId, $preferSites] = $this->site();

        $element = $this->elementQuery()
            ->uid($elementUid)
            ->siteId($siteId)
            ->preferSites($preferSites)
            ->unique()
            ->status(null)
            ->one();

        if ($element) {
            return $element;
        }

        // check for an unpublished draft if we got this far
        // (e.g. newly added matrix "block" or where autosaveDrafts is off)
        // https://github.com/craftcms/cms/issues/15985
        return $this->elementQuery()
            ->uid($elementUid)
            ->siteId($siteId)
            ->preferSites($preferSites)
            ->unique()
            ->status(null)
            ->draftOf(false)
            ->one();
    }

    private function canSave(ElementInterface $element, User $user): bool
    {
        if ($element->getIsRevision()) {
            return false;
        }

        if ($element->isProvisionalDraft) {
            $element = $element->getCanonical(true);
        }

        return $user->can('save', $element);
    }
}
