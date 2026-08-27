<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\Data;

use InvalidArgumentException;

readonly class ActivityChange
{
    /**
     * @param  string  $type  The kind of value that changed, such as `attribute` or `field`.
     * @param  string  $id  The changed value's stable identifier, such as an attribute name or field layout element UID.
     * @param  string  $label  The human-readable name captured when the change occurred.
     * @param  mixed  $old  The value before the change.
     * @param  mixed  $new  The value after the change.
     */
    public function __construct(
        public string $type,
        public string $id,
        public string $label,
        public mixed $old,
        public mixed $new,
    ) {
        if ($this->type === '' || $this->id === '' || $this->label === '') {
            throw new InvalidArgumentException('Activity changes require a type, ID, and label.');
        }
    }

    /** @return array{type: string, id: string, label: string, old: mixed, new: mixed} */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'label' => $this->label,
            'old' => $this->old,
            'new' => $this->new,
        ];
    }

    /** @param array{type: string, id: string, label: string, old: mixed, new: mixed} $change */
    public static function fromArray(array $change): self
    {
        return new self(
            $change['type'],
            $change['id'],
            $change['label'],
            $change['old'],
            $change['new'],
        );
    }
}
