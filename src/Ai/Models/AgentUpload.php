<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;

/**
 * A file a user uploaded to an AI assistant conversation, as an asset in their temporary uploads folder. It’s deleted
 * along with the conversation, unless it’s been moved out of that folder or used somewhere.
 *
 * @property int $id
 * @property string $conversationId The conversation the file was uploaded to, which may not be stored yet.
 * @property int $assetId
 * @property int $userId
 *
 * @internal
 *
 * @since 6.0.0
 */
class AgentUpload extends BaseModel
{
    use HasUid;

    #[\Override]
    protected $table = Table::AGENT_UPLOADS;

    #[\Override]
    protected function casts(): array
    {
        return [
            'dateCreated' => 'immutable_datetime',
            'dateUpdated' => 'immutable_datetime',
        ];
    }
}
