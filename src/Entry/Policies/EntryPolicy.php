<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Policies;

use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Element\Policies\ElementPolicy;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\User\Contracts\CraftUser;

class EntryPolicy extends ElementPolicy
{
    public function view(CraftUser $user, Entry $entry): bool
    {
        if (! $section = $entry->getSection()) {
            return false;
        }

        $userId = $user->getCraftUserId();

        if (! $user->can("viewEntries:$section->uid")) {
            return false;
        }

        if ($entry->getIsDraft()) {
            return $entry->draftCreatorId === $userId
                || $user->can("viewPeerEntryDrafts:$section->uid");
        }

        return $section->type === SectionType::Single
            || in_array($userId, $entry->getAuthorIds(), true)
            || $user->can("viewPeerEntries:$section->uid");
    }

    public function save(CraftUser $user, Entry $entry): bool
    {
        if (! $section = $entry->getSection()) {
            return false;
        }

        $userId = $user->getCraftUserId();

        if (! $entry->id) {
            return $section->type !== SectionType::Single
                && $user->can("createEntries:$section->uid");
        }

        if ($entry->getIsDraft()) {
            return $entry->draftCreatorId === $userId
                || $user->can("savePeerEntryDrafts:$section->uid");
        }

        if (! $user->can("saveEntries:$section->uid")) {
            return false;
        }

        return $section->type === SectionType::Single
            || in_array($userId, $entry->getAuthorIds(), true)
            || $user->can("savePeerEntries:$section->uid");
    }

    public function delete(CraftUser $user, Entry $entry): bool
    {
        if (! $section = $entry->getSection()) {
            return false;
        }

        $userId = $user->getCraftUserId();

        if ($section->type === SectionType::Single && ! $entry->getIsDraft()) {
            return false;
        }

        if ($entry->getIsDraft()) {
            return $entry->draftCreatorId === $userId
                || $user->can("deletePeerEntryDrafts:$section->uid");
        }

        if (! $user->can("deleteEntries:$section->uid")) {
            return false;
        }

        return in_array($userId, $entry->getAuthorIds(), true)
            || $user->can("deletePeerEntries:$section->uid");
    }

    public function duplicate(CraftUser $user, Entry $entry): bool
    {
        if (! $section = $entry->getSection()) {
            return false;
        }

        return $section->type !== SectionType::Single
            && $user->can("createEntries:$section->uid")
            && $user->can("saveEntries:$section->uid");
    }

    public function duplicateAsDraft(CraftUser $user, Entry $entry): bool
    {
        if (! $section = $entry->getSection()) {
            return false;
        }

        return $section->type !== SectionType::Single
            && $user->can("createEntries:$section->uid");
    }

    public function copy(CraftUser $user, Entry $entry): bool
    {
        return $this->view($user, $entry);
    }

    public function createDrafts(CraftUser $user, Entry $entry): bool
    {
        return true;
    }

    public function deleteForSite(CraftUser $user, Entry $entry): bool
    {
        if (! $section = $entry->getSection()) {
            return false;
        }

        $userId = $user->getCraftUserId();

        if ($section->propagationMethod !== PropagationMethod::Custom) {
            return false;
        }

        if ($entry->getIsDraft()) {
            return $entry->draftCreatorId === $userId
                || $user->can("deletePeerEntryDrafts:$section->uid");
        }

        if (! $user->can("deleteEntriesForSite:$section->uid")) {
            return false;
        }

        return in_array($userId, $entry->getAuthorIds(), true)
            || $user->can("deletePeerEntriesForSite:$section->uid");
    }
}
