<?php

declare(strict_types=1);

namespace CraftCms\Cms\Updates\Data;

use CraftCms\Cms\Updates\Enums\UpdateStatus;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @internal
 */
readonly class Update implements Arrayable
{
    public function __construct(
        public UpdateStatus $status = UpdateStatus::ELIGIBLE,
        public string $packageName = '',
        public ?float $renewalPrice = null,
        public ?string $renewalCurrency = null,
        public ?string $renewalUrl = null,
        /** @var UpdateRelease[] */
        public array $releases = [],
        public ?string $phpConstraint = null,
        public bool $abandoned = false,
        public ?string $replacementName = null,
        public ?string $replacementHandle = null,
        public ?string $replacementUrl = null,
    ) {}

    public function hasReleases(): bool
    {
        return ! empty($this->releases);
    }

    public function hasCritical(): bool
    {
        return array_any(
            $this->releases,
            fn (UpdateRelease $release) => $release->isCritical($this),
        );
    }

    public function latest(): ?UpdateRelease
    {
        return $this->releases[0] ?? null;
    }

    public static function fromArray(array $data): self
    {
        $data['status'] = UpdateStatus::tryFrom($data['status'] ?? '');
        $data['releases'] = array_map(
            UpdateRelease::fromArray(...),
            $data['releases'] ?? [],
        );
        $data['abandoned'] = (bool) ($data['abandoned'] ?? false);

        return new self(...$data);
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'packageName' => $this->packageName,
            'renewalPrice' => $this->renewalPrice,
            'renewalCurrency' => $this->renewalCurrency,
            'renewalUrl' => $this->renewalUrl,
            'releases' => $this->releases,
            'phpConstraint' => $this->phpConstraint,
            'abandoned' => $this->abandoned,
            'replacementName' => $this->replacementName,
            'replacementHandle' => $this->replacementHandle,
            'replacementUrl' => $this->replacementUrl,
        ];
    }
}
