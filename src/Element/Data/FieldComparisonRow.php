<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * One field on the Compare screen: its layout element UID in each version’s form, and whether it changed between them.
 *
 * @implements Arrayable<string, mixed>
 *
 * @since 6.0.0
 */
readonly class FieldComparisonRow implements Arrayable
{
    /**
     * @param  string|null  $fromUid  `null` when only the newer version has the field.
     * @param  string|null  $toUid  `null` when only the older version has the field.
     * @param  bool|null  $changed  `null` when it can’t be told, such as for Matrix fields shown as cards.
     */
    public function __construct(
        public ?string $fromUid,
        public ?string $toUid,
        public ?bool $changed,
    ) {}

    /**
     * @return array{fromUid: string|null, toUid: string|null, changed: bool|null}
     */
    public function toArray(): array
    {
        return [
            'fromUid' => $this->fromUid,
            'toUid' => $this->toUid,
            'changed' => $this->changed,
        ];
    }
}
