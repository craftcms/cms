<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Support\Str;

/**
 * A skill for the AI assistant: instructions for a kind of task, which the assistant follows when a task matches its
 * description, or the user chooses it with `/`. Skills apply to the whole site.
 *
 * @property int $id
 * @property string $title Unique.
 * @property string $body
 * @property string $description What the skill does and when to use it, which the assistant chooses skills by.
 *
 * @since 6.0.0
 */
class AgentSkill extends BaseModel
{
    use HasUid;

    #[\Override]
    protected $table = Table::AGENT_SKILLS;

    #[\Override]
    protected function casts(): array
    {
        return [
            'dateCreated' => 'immutable_datetime',
            'dateUpdated' => 'immutable_datetime',
        ];
    }

    /**
     * The name the assistant’s model knows the skill by. Titles can differ only in case or punctuation, which a slug
     * loses, so it ends with the ID.
     */
    public function name(): string
    {
        $slug = Str::slug(Str::ascii($this->title)) ?: 'skill';

        return Str::limit($slug, 50, '')."-{$this->id}";
    }
}
