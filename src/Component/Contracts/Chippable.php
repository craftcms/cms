<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

/**
 * Chippable defines the common interface to be implemented by components
 * that can be displayed as chips in the control panel.
 */
interface Chippable extends Identifiable
{
    /**
     * Returns a component by its ID.
     */
    public static function get(string|int $id): ?static;

    /**
     * Returns what the component should be called within the control panel.
     */
    public function getUiLabel(): string;
}
