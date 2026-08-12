<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Resources;

use CraftCms\Cms\Cp\JsonResource;
use CraftCms\Cms\Gql\Data\GqlToken;
use DateTimeInterface;
use Illuminate\Http\Request;

/**
 * @mixin GqlToken
 */
class GqlTokenResource extends JsonResource
{
    /**
     * @return array{id: ?int, name: ?string, dateCreated: ?string, lastUsed: ?string, expiryDate: ?string, enabled: bool, isTemporary: bool, isValid: bool, isExpired: bool, isPublic: bool}
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'dateCreated' => $this->dateCreated?->format(DateTimeInterface::ATOM),
            'lastUsed' => $this->lastUsed?->format(DateTimeInterface::ATOM),
            'expiryDate' => $this->expiryDate?->format(DateTimeInterface::ATOM),
            'enabled' => $this->enabled,
            'isTemporary' => $this->isTemporary,
            'isValid' => $this->isValid,
            'isExpired' => $this->isExpired,
            'isPublic' => $this->isPublic,
        ];
    }
}
