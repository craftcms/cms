<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;

/**
 * Guidance for the AI assistant. Without a site or user, it applies to every site. With a site, it’s added for that
 * site. With a user, it’s that user’s own.
 *
 * @property int $id
 * @property int|null $siteId
 * @property int|null $userId
 * @property string $body
 *
 * @since 6.0.0
 */
class AgentGuidance extends BaseModel
{
    use HasUid;

    #[\Override]
    protected $table = Table::AGENT_GUIDANCE;

    #[\Override]
    protected function casts(): array
    {
        return [
            'siteId' => 'integer',
            'userId' => 'integer',
            'dateCreated' => 'immutable_datetime',
            'dateUpdated' => 'immutable_datetime',
        ];
    }
}
