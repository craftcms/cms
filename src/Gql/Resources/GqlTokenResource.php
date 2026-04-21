<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Resources;

use CraftCms\Cms\Gql\Data\GqlToken;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GqlToken
 */
class GqlTokenResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'dateCreated' => $this->dateCreated?->format(DateTime::ATOM),
            'lastUsed' => $this->lastUsed?->format(DateTime::ATOM),
            'expiryDate' => $this->expiryDate?->format(DateTime::ATOM),
            'enabled' => $this->enabled,
            'isTemporary' => $this->isTemporary,
            'isValid' => $this->isValid,
            'isExpired' => $this->isExpired,
            'isPublic' => $this->isPublic,
        ];
    }
}
