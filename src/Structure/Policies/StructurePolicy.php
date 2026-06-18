<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure\Policies;

use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Structure\Data\Structure;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Contracts\CraftUser;

class StructurePolicy
{
    public function edit(CraftUser $user, Structure $structure): bool
    {
        if (! $structure->id) {
            return false;
        }

        $section = Sections::getAllSections()
            ->first(fn ($section) => $section->type === SectionType::Structure && $section->structureId === $structure->id);

        if (! $section) {
            return false;
        }

        return $user->can("saveEntries:$section->uid");
    }
}
