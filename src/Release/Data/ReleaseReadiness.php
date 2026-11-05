<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 *
 * @since 6.0.0
 */
readonly class ReleaseReadiness implements Arrayable
{
    /**
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     * @param  array<int, list<string>>  $itemErrors
     * @param  array<int, list<string>>  $itemWarnings
     */
    public function __construct(
        public bool $ready,
        public array $errors = [],
        public array $warnings = [],
        public array $itemErrors = [],
        public array $itemWarnings = [],
    ) {}

    /** @return array{ready: bool, errors: list<string>, warnings: list<string>, itemErrors: array<int, list<string>>, itemWarnings: array<int, list<string>>} */
    public function toArray(): array
    {
        return [
            'ready' => $this->ready,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'itemErrors' => $this->itemErrors,
            'itemWarnings' => $this->itemWarnings,
        ];
    }
}
