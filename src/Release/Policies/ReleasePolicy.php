<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Policies;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Release\Enums\ReleaseStatus;
use CraftCms\Cms\Release\Models\Release;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Support\Facades\Gate;

/**
 * @since 6.0.0
 */
class ReleasePolicy
{
    public function manage(CraftUser $actor, Release $release): bool
    {
        return $actor->can('manageReleases') && $release->status !== ReleaseStatus::Published;
    }

    public function attach(CraftUser $actor, Release $release, ElementInterface $draft): bool
    {
        return $release->status->isMutable()
            && $draft::hasDrafts()
            && (! $draft instanceof NestedElementInterface || $draft->getPrimaryOwnerId() === null)
            && $this->canProspectivelyPublish($draft, $actor);
    }

    public function detach(CraftUser $actor, Release $release, ElementInterface $draft): bool
    {
        return $release->status->isMutable() && $this->publishDraft($actor, $draft);
    }

    public function publish(CraftUser $actor, Release $release): bool
    {
        return $actor->can('publishReleases')
            && $release->status->isMutable()
            && $release->items()->exists();
    }

    public function publishDraft(CraftUser $actor, ElementInterface $draft): bool
    {
        if (is_int($draft->draftId) && $draft->draftId > 0) {
            $siteDrafts = $draft::find()
                ->draftId($draft->draftId)
                ->site('*')
                ->status(null)
                ->all();
        } else {
            $siteDrafts = $this->canonicalPublicationCandidates($draft);
        }

        return $this->canPublishCandidates($siteDrafts, $actor);
    }

    private function canProspectivelyPublish(ElementInterface $draft, CraftUser $actor): bool
    {
        if (! $draft->isProvisionalDraft) {
            return $this->publishDraft($actor, $draft);
        }

        return $this->canPublishCandidates($this->canonicalPublicationCandidates($draft), $actor);
    }

    /** @param list<ElementInterface> $candidates */
    private function canPublishCandidates(array $candidates, CraftUser $actor): bool
    {
        foreach ($candidates as $candidate) {
            if (! Gate::forUser($actor)->check('save', $candidate)
                || ! Gate::forUser($actor)->check('saveCanonical', $candidate)
            ) {
                return false;
            }
        }

        return $candidates !== [];
    }

    /** @return list<ElementInterface> */
    private function canonicalPublicationCandidates(ElementInterface $element): array
    {
        $siteElements = is_int($element->id) && $element->id > 0
            ? $element::find()
                ->id($element->id)
                ->drafts(null)
                ->revisions(null)
                ->site('*')
                ->status(null)
                ->all()
            : [];
        $siteElementsBySite = collect($siteElements)->keyBy('siteId');

        foreach (ElementHelper::supportedSitesForElement($element) as $siteInfo) {
            if ($siteElementsBySite->has($siteInfo['siteId'])) {
                continue;
            }

            $siteElement = clone $element;
            $siteElement->siteId = $siteInfo['siteId'];
            $siteElementsBySite->put($siteInfo['siteId'], $siteElement);
        }

        return $siteElementsBySite->values()->all();
    }
}
