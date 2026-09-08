<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\Data;

use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use InvalidArgumentException;

readonly class ActivitySource
{
    public function __construct(
        public string $id,
        public string $label,
        public string $translationCategory = 'app',
    ) {
        if ($this->id === '' || $this->label === '' || $this->translationCategory === '') {
            throw new InvalidArgumentException('Activity sources require an ID, label, and translation category.');
        }
    }

    public static function fromPlugin(PluginInterface $plugin): self
    {
        return new self(
            $plugin->handle,
            $plugin->name ?? $plugin->handle,
            $plugin->t9nCategory ?? $plugin->handle,
        );
    }
}
