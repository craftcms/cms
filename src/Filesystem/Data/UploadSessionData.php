<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Data;

use CraftCms\Cms\Filesystem\Models\UploadSession;
use Illuminate\Contracts\Support\Arrayable;

/** @implements Arrayable<string, mixed> */
readonly class UploadSessionData implements Arrayable
{
    /**
     * @param  array{type: string, options: array<string, mixed>}  $transport
     * @param  array{transfer: string, status: string, complete: string, cancel: string}  $urls
     */
    public function __construct(
        public string $id,
        public int $chunkSize,
        public int $partCount,
        public array $transport,
        public array $urls,
    ) {}

    public static function fromSession(UploadSession $session, UploadSetup $setup): self
    {
        $prefix = request()->isCpRequest() ? 'craft.actions.craft.cp.uploads' : 'craft.actions.craft.uploads';

        return new self(
            id: $session->id,
            chunkSize: $session->chunkSize,
            partCount: $session->partCount(),
            transport: [
                'type' => $setup->transportType,
                'options' => $setup->transportOptions,
            ],
            urls: [
                'transfer' => route("$prefix.transfer", ['upload' => $session->id]),
                'status' => route("$prefix.status", ['upload' => $session->id]),
                'complete' => route("$prefix.complete", ['upload' => $session->id]),
                'cancel' => route("$prefix.destroy", ['upload' => $session->id]),
            ],
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
