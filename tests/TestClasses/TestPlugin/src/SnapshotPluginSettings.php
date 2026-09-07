<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\TestPlugin\src;

use CraftCms\Cms\Plugin\PluginSettings;

class SnapshotPluginSettings extends PluginSettings
{
    public bool $enabled = false;

    public ?string $title = 'Default';

    public array $nested = ['default' => true];

    public mixed $callback = null;

    private readonly string $constructorValue;

    public function __construct(array|object $config = [])
    {
        $this->constructorValue = 'constructed';
        parent::__construct($config);
    }

    public function title(?string $value): static
    {
        $this->title = $value === null ? null : trim($value);

        return $this;
    }

    public function getConstructorValue(): string
    {
        return $this->constructorValue;
    }
}
