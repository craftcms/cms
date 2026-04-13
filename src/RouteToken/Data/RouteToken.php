<?php

declare(strict_types=1);

namespace CraftCms\Cms\RouteToken\Data;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use Illuminate\Validation\Rule;

class RouteToken extends Component
{
    /** @var class-string<ElementInterface> */
    public string $elementType;

    public int $siteId;

    public ?int $canonicalId = null;

    public ?int $sourceId = null;

    public ?int $draftId = null;

    public ?int $revisionId = null;

    public ?int $userId = null;

    public ?string $previewToken = null;

    public ?string $redirect = null;

    #[\Override]
    public function getRules(): array
    {
        return [
            'elementType' => ['required', 'string'],
            'siteId' => ['required', 'integer', Rule::exists(Table::SITES, 'id')],
            'canonicalId' => ['nullable', 'required_without:sourceId'],
            'sourceId' => ['nullable', 'required_without:canonicalId'],
            'draftId' => ['nullable', 'integer'],
            'revisionId' => ['nullable', 'integer'],
            'userId' => ['nullable', 'integer', Rule::exists(Table::USERS, 'id')],
            'previewToken' => ['nullable', 'string'],
            'redirect' => ['nullable', 'string'],
        ];
    }

    public function getCanonicalId(): int
    {
        return $this->canonicalId ?? $this->sourceId;
    }
}
