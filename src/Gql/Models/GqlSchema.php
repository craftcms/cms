<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class GqlSchema extends BaseModel
{
    use HasUid;

    protected $table = Table::GQLSCHEMAS;

    #[\Override]
    protected function casts(): array
    {
        return [
            'scope' => 'json',
            'isPublic' => 'bool',
        ];
    }

    /**
     * @return HasMany<GqlToken, $this>
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(GqlToken::class, 'schemaId');
    }
}
