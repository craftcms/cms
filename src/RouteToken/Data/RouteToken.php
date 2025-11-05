<?php

declare(strict_types=1);

namespace CraftCms\Cms\RouteToken\Data;

use Spatie\LaravelData\Attributes\Validation\RequiredWithout;
use Spatie\LaravelData\Dto;

final class RouteToken extends Dto
{
    public function __construct(
        /** @var class-string<\craft\base\ElementInterface> */
        public string $elementType,
        public int $siteId,
        #[RequiredWithout('sourceId')]
        public ?int $canonicalId,
        #[RequiredWithout('canonicalId')]
        public ?int $sourceId,
        public ?int $draftId = null,
        public ?int $revisionId = null,
        public ?int $userId = null,
        public ?string $previewToken = null,
        public ?string $redirect = null,
    ) {}

    public function getCanonicalId(): int
    {
        return $this->canonicalId ?? $this->sourceId;
    }
}
