<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class GqlToken extends BaseModel
{
    use HasUid;

    #[\Override]
    protected $table = Table::GQLTOKENS;

    #[\Override]
    protected function casts(): array
    {
        return [
            'enabled' => 'bool',
            'expiryDate' => 'datetime',
            'lastUsed' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<GqlSchema, $this>
     */
    public function schema(): BelongsTo
    {
        return $this->belongsTo(GqlSchema::class, 'schemaId');
    }
}
