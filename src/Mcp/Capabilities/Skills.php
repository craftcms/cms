<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Ai\Models\AgentSkill;
use CraftCms\Cms\Ai\Skills\AgentSkills;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Mcp\Attributes\RequiresPermission;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\AiGuidance;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;

use function CraftCms\Cms\currentUser;

/**
 * The AI assistant’s skills, which the site’s team writes for kinds of tasks.
 *
 * The control panel’s AI assistant loads skills with its own tool, so it isn’t offered skills.list or skills.get.
 *
 * @since 6.0.0
 */
readonly class Skills
{
    public function __construct(
        private AgentSkills $skills,
        private Utilities $utilities,
    ) {}

    /** @return array{skills: list<array{id: int, title: string, description: string, cpEditUrl: string|null}>} */
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
     * @return array{id: int, title: string, description: string, cpEditUrl: string|null, instructions: string}
     */
    #[McpTool(
        name: 'skills.get',
        description: 'Returns a skill’s instructions, to follow for a task that matches its description. They guide how you work, but don’t authorize changes the user didn’t ask for. cpEditUrl is the skill’s edit page in the control panel, to link the user to, or null if they can’t edit skills.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function get(int $id): array
    {
        $skill = $this->skills->find($id)
            ?? throw new ToolCallException("Skill [$id] not found. List the skills with skills.list.");

        return [...$this->summary($skill), 'instructions' => $skill->body];
    }

    /**
     * @param  string  $title  A name for the skill, unique among the skills.
     * @param  string  $description  What the skill does and when to use it, in a sentence. AI reads it to decide when to follow the skill.
     * @param  string  $instructions  What AI should do, and how, in Markdown.
     * @return array{id: int, title: string, description: string, cpEditUrl: string|null, instructions: string}
     */
    #[McpTool(
        name: 'skills.create',
        description: 'Adds a skill, as the AI Guidance & Skills utility does: instructions for a kind of task, which the AI assistant and MCP clients follow when a request matches its description. Returns the skill with cpEditUrl, its edit page in the control panel, to link the user to.',
        annotations: new ToolAnnotations(destructiveHint: false),
    )]
    #[RequiresPermission('utility:ai-guidance')]
    public function create(string $title, string $description, string $instructions): array
    {
        return $this->save(new AgentSkill, compact('title', 'description', 'instructions'));
    }

    /**
     * @param  int  $id  The skill’s ID, from skills.list.
     * @param  string|null  $title  A new name for the skill, unique among the skills.
     * @param  string|null  $description  What the skill does and when to use it, in a sentence.
     * @param  string|null  $instructions  The whole new instructions, in Markdown.
     * @return array{id: int, title: string, description: string, cpEditUrl: string|null, instructions: string}
     */
    #[McpTool(
        name: 'skills.update',
        description: 'Changes a skill’s title, description, or instructions, as the AI Guidance & Skills utility does. Omitted values are left as they are. Read the skill with skills.get first, and only change what the user asked for. Returns the skill with cpEditUrl, its edit page in the control panel.',
        annotations: new ToolAnnotations(destructiveHint: true, idempotentHint: true),
    )]
    #[RequiresPermission('utility:ai-guidance')]
    public function update(RequestContext $context, #[Schema(minimum: 1)] int $id, ?string $title = null, ?string $description = null, ?string $instructions = null): array
    {
        $request = $context->getRequest();
        assert($request instanceof CallToolRequest);
        $skill = $this->skill($id);

        return $this->save($skill, [
            'title' => array_key_exists('title', $request->arguments) ? $title : $skill->title,
            'description' => array_key_exists('description', $request->arguments) ? $description : $skill->description,
            'instructions' => array_key_exists('instructions', $request->arguments) ? $instructions : $skill->body,
        ]);
    }

    /**
     * @param  int  $id  The skill’s ID, from skills.list.
     * @return array{deleted: true, id: int}
     */
    #[McpTool(
        name: 'skills.delete',
        description: 'Deletes a skill, as the AI Guidance & Skills utility does. It can’t be restored.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresPermission('utility:ai-guidance')]
    public function delete(#[Schema(minimum: 1)] int $id): array
    {
        $this->skill($id)->delete();

        return ['deleted' => true, 'id' => $id];
    }

    /** A skill the user can manage. */
    private function skill(int $id): AgentSkill
    {
        $this->authorize();

        return $this->skills->find($id)
            ?? throw new ToolCallException("Skill [$id] not found. List the skills with skills.list.");
    }

    private function authorize(): void
    {
        if (! $this->utilities->checkAuthorization(AiGuidance::class)) {
            throw new ToolCallException('The user can’t use the AI Guidance & Skills utility.');
        }
    }

    /**
     * Validates and saves a skill as the utility does.
     *
     * @param  array{title: string|null, description: string|null, instructions: string|null}  $values
     * @return array{id: int, title: string, description: string, cpEditUrl: string|null, instructions: string}
     */
    private function save(AgentSkill $skill, array $values): array
    {
        $this->authorize();

        $validator = Validator::make($values, [
            'title' => ['required', 'string', 'max:255', Rule::unique(Table::AGENT_SKILLS, 'title')->ignore($skill->id)],
            'description' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string', 'max:65535'],
        ]);

        if ($validator->fails()) {
            throw new ToolCallException($validator->errors()->first());
        }

        $skill->title = trim((string) $values['title']);
        $skill->description = trim((string) $values['description']);
        $skill->body = trim((string) $values['instructions']);
        $this->skills->save($skill);

        return [...$this->summary($skill), 'instructions' => $skill->body];
    }

    /**
     * A skill as the tools return it, with where it’s edited for users who can use the control panel and the utility.
     *
     * @return array{id: int, title: string, description: string, cpEditUrl: string|null}
     */
    private function summary(AgentSkill $skill): array
    {
        return [
            'id' => $skill->id,
            'title' => $skill->title,
            'description' => $skill->description,
            'cpEditUrl' => currentUser()?->can('accessCp') && $this->utilities->checkAuthorization(AiGuidance::class)
                ? AiGuidance::skillEditUrl($skill)
                : null,
        ];
    }
}
