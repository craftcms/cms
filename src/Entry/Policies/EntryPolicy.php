<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Policies;

use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Element\Policies\ElementPolicy;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Contracts\CraftUser;
use RuntimeException;

/**
 * @since 6.0.0
 */
class EntryPolicy extends ElementPolicy
{
    public function view(CraftUser $user, Entry $entry): bool
    {
        if (! $section = $this->section($entry)) {
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
        if (! $section = $this->section($entry)) {
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
        if (! $section = $this->section($entry)) {
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
        if (! $section = $this->section($entry)) {
            return false;
        }

        return $section->type !== SectionType::Single
            && $user->can("createEntries:$section->uid")
            && $user->can("saveEntries:$section->uid");
    }

    public function duplicateAsDraft(CraftUser $user, Entry $entry): bool
    {
        if (! $section = $this->section($entry)) {
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
        if (! $section = $this->section($entry)) {
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

    public function changeAuthor(CraftUser $user, Entry $entry): bool
    {
        $section = $this->section($entry);

        if (! $section) {
            return false;
        }

        $authorIds = $entry->getAuthorIds();
        $userId = $user->getCraftUserId();

        return
            empty($authorIds) ||
            ($userId !== null && in_array($userId, $authorIds, true)) ||
            $user->can("changeAuthorForPeerEntries:$section->uid");
    }

    public function author(CraftUser $user, Entry $entry): bool
    {
        $section = $this->section($entry);

        if (! $section) {
            return false;
        }

        return $user->can("viewEntries:$section->uid");
    }

    public function move(CraftUser $user, Entry $entry): bool
    {
        if (! $section = $this->section($entry)) {
            return false;
        }

        $userId = $user->getCraftUserId();

        if ($section->type === SectionType::Single || $entry->trashed) {
            return false;
        }

        if (! $this->hasCompatibleSection($user, $entry)) {
            return false;
        }

        if ($entry->getIsDraft()) {
            return ($userId !== null && $entry->draftCreatorId === $userId) ||
                $user->can("savePeerEntryDrafts:$section->uid");
        }

        if (! $user->can("saveEntries:$section->uid")) {
            return false;
        }

        return ($userId !== null && in_array($userId, $entry->getAuthorIds(), true)) ||
            $user->can("savePeerEntries:$section->uid");
    }

    public function moveToSection(CraftUser $user, Entry $entry, Section $section): bool
    {
        return $this->move($user, $entry) &&
            $user->can("saveEntries:$section->uid");
    }

    /**
     * Returns the entry's section, or null if it doesn't have one or the section was deleted.
     */
    private function section(Entry $entry): ?Section
    {
        try {
            return $entry->getSection();
        } catch (RuntimeException) {
            return null;
        }
    }

    /**
     * Returns whether the entry's type is available in another non-single section the user can view.
     *
     * It checks the user's permission last and stops at the first matching section, because element indexes call it for
     * every entry they show.
     */
    private function hasCompatibleSection(CraftUser $user, Entry $entry): bool
    {
        $entryTypeId = $entry->getType()->id;

        return Sections::getAllSections()->contains(fn (Section $section) => $section->type !== SectionType::Single
            && $section->id !== $entry->sectionId
            && collect($section->getEntryTypes())->contains('id', $entryTypeId)
            && $user->can("viewEntries:$section->uid"));
    }
}
