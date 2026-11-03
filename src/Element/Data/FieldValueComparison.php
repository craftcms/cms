<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * A field layout element’s value on a draft, revision, or nested element, and the value it’s compared with.
 *
 * Values are strings, booleans, `null`, or lists of element references (`elementType`, `id`, `label`, `siteId`),
 * the same shapes the activity timeline shows changes in.
 *
 * @implements Arrayable<string, mixed>
 *
 * @since 6.0.0
 */
readonly class FieldValueComparison implements Arrayable
{
    /**
     * @param  string  $fromLabel  What the value is compared with, such as “Current” or “Revision 4”.
     * @param  mixed  $from  The value being compared with.
     * @param  string  $toLabel  What has the value, such as a draft’s name or “Revision 5”.
     * @param  mixed  $to  The value.
     * @param  bool  $hasBase  Whether there is anything to compare with, such as an earlier revision.
     */
    public function __construct(
        public string $fromLabel,
        public mixed $from,
        public string $toLabel,
        public mixed $to,
        public bool $hasBase = true,
    ) {}

    /**
     * Returns whether both values are the same.
     */
    public function isUnchanged(): bool
    {
        return $this->hasBase && $this->from === $this->to;
    }

    /**
     * @return array{from: array{label: string, value: mixed}, to: array{label: string, value: mixed}, hasBase: bool, unchanged: bool}
     */
    public function toArray(): array
    {
        return [
            'from' => ['label' => $this->fromLabel, 'value' => $this->from],
            'to' => ['label' => $this->toLabel, 'value' => $this->to],
            'hasBase' => $this->hasBase,
            'unchanged' => $this->isUnchanged(),
        ];
    }
}
