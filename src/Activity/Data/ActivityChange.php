<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\Data;

use InvalidArgumentException;

readonly class ActivityChange
{
    /**
     * @param  string  $label  The human-readable name captured when the change occurred.
     * @param  mixed  $old  The value before the change.
     * @param  mixed  $new  The value after the change.
     */
    public function __construct(
        public string $label,
        public mixed $old,
        public mixed $new,
    ) {
        if ($this->label === '') {
            throw new InvalidArgumentException('Activity changes require a label.');
        }
    }

    /** @return array{label: string, old: mixed, new: mixed} */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'old' => $this->old,
            'new' => $this->new,
        ];
    }

    /** @param array{label: string, old: mixed, new: mixed} $change */
    public static function fromArray(array $change): self
    {
        return new self(
            $change['label'],
            $change['old'],
            $change['new'],
        );
    }
}
