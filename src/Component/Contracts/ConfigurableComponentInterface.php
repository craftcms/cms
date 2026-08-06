<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;

interface ConfigurableComponentInterface
{
    /**
     * Returns the component's renderer-neutral settings Form.
     *
     * Return `null` if the component has no settings Form.
     */
    public function settingsForm(FormContext $context = new FormContext): ?Form;

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
     * @return array<string, mixed> The component’s settings.
     */
    public function getSettings(): array;
}
