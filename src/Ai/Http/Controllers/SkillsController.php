<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Http\Controllers;

use CraftCms\Cms\Ai\Models\AgentSkill;
use CraftCms\Cms\Ai\Skills\AgentSkills;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\AiGuidance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * Creates, edits, and deletes the AI assistant’s skills from the AI Guidance & Skills utility.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class SkillsController
{
    use RespondsWithFlash;

    public function __construct(
        private AgentSkills $skills,
        Utilities $utilities,
    ) {
        abort_unless($utilities->checkAuthorization(AiGuidance::class), 403, 'User is not authorized to perform this action.');
    }

    /**
     * Returns the skills as the utility’s table lists them, for it to refresh once one is saved.
     */
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'skills' => $this->skills->all()
                ->map(fn (AgentSkill $skill): array => AiGuidance::skillData($skill))
                ->values()
                ->all(),
        ]);
    }

    public function create(): CpScreenResponse
    {
        return $this->editScreen(null);
    }

    public function edit(int $skillId): CpScreenResponse
    {
        return $this->editScreen($this->skills->find($skillId) ?? abort(404, 'Skill not found.'));
    }

    public function store(Request $request): Response
    {
        return $this->save($request, new AgentSkill);
    }

    public function update(Request $request, int $skillId): Response
    {
        return $this->save($request, $this->skills->find($skillId) ?? abort(404, 'Skill not found.'));
    }

    public function destroy(int $skillId): Response
    {
        ($this->skills->find($skillId) ?? abort(404, 'Skill not found.'))->delete();

        return $this->asSuccess(t('Skill deleted.'));
    }

    private function editScreen(?AgentSkill $skill): CpScreenResponse
    {
        $title = $skill->title ?? t('New Skill');

        return new CpScreenResponse()
            ->title($title)
            ->docTitle($title)
            ->crumbs([
                new ActionItem()->label(t('Utilities'))->href(Url::cpUrl('utilities')),
                new ActionItem()->label(AiGuidance::displayName())->href(Url::cpUrl('utilities/'.AiGuidance::id())),
                new ActionItem()->label(t('Skills'))->href(AiGuidance::skillsUrl()),
                new ActionItem()->label($title),
            ])
            ->redirectUrl('utilities/'.AiGuidance::id().'/skills')
            ->inertiaPage('ai/skills/Edit', [
                'skill' => $skill ? AiGuidance::skillData($skill) : null,
            ]);
    }

    private function save(Request $request, AgentSkill $skill): Response
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique(Table::AGENT_SKILLS, 'title')->ignore($skill->id)],
            'description' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:65535'],
        ]);

        $isNew = ! $skill->exists;
        $skill->title = trim($validated['title']);
        $skill->description = trim($validated['description']);
        $skill->body = trim($validated['body']);

        $this->skills->save($skill);

        $skillData = AiGuidance::skillData($skill);

        // A page that stays open after creating a skill goes on to edit it, so saving again doesn't create another.
        $redirect = $this->getPostedRedirectUrl() ?? ($isNew && ! $request->expectsJson() ? $skillData['editUrl'] : null);

        return $this->asSuccess(t('Skill saved.'), data: ['skill' => $skillData], redirect: $redirect);
    }
}
