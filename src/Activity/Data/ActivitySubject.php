<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\Data;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use InvalidArgumentException;

readonly class ActivitySubject
{
    public function __construct(
        public string $type,
        public string $id,
        public string $label,
    ) {
        if ($this->type === '' || $this->id === '' || $this->label === '') {
            throw new InvalidArgumentException('Activity subjects require a type, ID, and label.');
        }
    }

    public static function fromElement(ElementInterface $element): self
    {
        $canonical = $element->getCanonical();

        if ($canonical->uid === null) {
            throw new InvalidArgumentException('Activity subjects must be saved elements.');
        }

        $label = $canonical->getUiLabel();

        return new self(
            $canonical::class,
            $canonical->uid,
            $label !== '' ? $label : sprintf('%s %s', $canonical::displayName(), $canonical->id),
        );
    }
}
