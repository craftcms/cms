<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;

interface ConfigurableComponentInterface
{
    /**
     * Returns the list of settings attribute names.
     *
     * By default, this method returns all public non-static properties that were defined on the called class.
     * You may override this method to change the default behavior.
     *
     * @return string[] The list of settings attribute names and values
     *
     * @see getSettings()
     */
    public function settingsAttributes(): array;

    /**
     * Returns an array of the component’s settings.
     *
     * @return array The component’s settings.
     */
    public function getSettings(): array;

    /**
     * Returns the component's settings Form Definition.
     *
     * A null result means that the component has no settings.
     */
    public function getSettingsFormDefinition(bool $readOnly): ?FormDefinition;
}
