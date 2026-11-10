<?php

declare(strict_types=1);

namespace crafttests\_support;

use CraftCms\Yii2Adapter\LooseEnds\LegacyUsage;

/**
 * Collects the keys of the legacy usage the adapter reports.
 *
 * Usage is normally only kept when project code is responsible for it, and a test’s calls come from the adapter’s own
 * tests, so they’d be dropped. The spy keeps every key, so a test can check what was reported.
 */
class LegacyUsageSpy extends LegacyUsage
{
    /** @var list<string> */
    public array $keys = [];

    /**
     * Binds a spy in place of the adapter’s legacy usage recorder.
     */
    public static function bind(): self
    {
        $spy = app(self::class);
        app()->instance(LegacyUsage::class, $spy);

        return $spy;
    }

    /**
     * Restores the adapter’s own legacy usage recorder.
     */
    public static function unbind(): void
    {
        app()->forgetInstance(LegacyUsage::class);
    }

    public function add(string $key, mixed ...$rest): void
    {
        $this->keys[] = $key;
    }
}
