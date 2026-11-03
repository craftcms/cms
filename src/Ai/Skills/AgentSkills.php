<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Skills;

use CraftCms\Cms\Ai\Models\AgentSkill;
use Illuminate\Database\Eloquent\Collection;

/**
 * Finds and saves the AI assistant’s skills, which apply to the whole site.
 *
 * @since 6.0.0
 */
readonly class AgentSkills
{
    /**
     * Returns all skills, by title.
     *
     * @return Collection<int, AgentSkill>
     */
    public function all(): Collection
    {
        return AgentSkill::query()
            ->orderBy('title')
            ->orderBy('id')
            ->get();
    }

    /**
     * Returns a skill by its ID.
     */
    public function find(int $id): ?AgentSkill
    {
        return AgentSkill::query()->find($id);
    }

    /**
     * Saves a skill.
     */
    public function save(AgentSkill $skill): void
    {
        $skill->save();
    }
}
