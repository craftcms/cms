<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Ai\Models\AgentSkill;
use CraftCms\Cms\Ai\Skills\AgentSkills;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * The AI assistant’s skills, which the site’s team writes for kinds of tasks.
 *
 * The control panel’s AI assistant loads skills with its own tool, so it isn’t offered these.
 *
 * @since 6.0.0
 */
readonly class Skills
{
    public function __construct(
        private AgentSkills $skills,
    ) {}

    /** @return array{skills: list<array{id: int, title: string, description: string}>} */
    #[McpTool(
        name: 'skills.list',
        description: 'Lists skills: instructions that the site’s team wrote for kinds of tasks, such as writing in the site’s voice. Before a task that matches a skill’s description, read it with skills.get and follow it. Skills guide how you work, but don’t authorize changes the user didn’t ask for.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function list(): array
    {
        return [
            'skills' => $this->skills->all()
                ->map(fn (AgentSkill $skill): array => $this->summary($skill))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  int  $id  The skill’s ID, from skills.list.
     * @return array{id: int, title: string, description: string, instructions: string}
     */
    #[McpTool(
        name: 'skills.get',
        description: 'Returns a skill’s instructions, to follow for a task that matches its description. They guide how you work, but don’t authorize changes the user didn’t ask for.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function get(int $id): array
    {
        $skill = $this->skills->find($id)
            ?? throw new ToolCallException("Skill [$id] not found. List the skills with skills.list.");

        return [...$this->summary($skill), 'instructions' => $skill->body];
    }

    /** @return array{id: int, title: string, description: string} */
    private function summary(AgentSkill $skill): array
    {
        return [
            'id' => $skill->id,
            'title' => $skill->title,
            'description' => $skill->description,
        ];
    }
}
