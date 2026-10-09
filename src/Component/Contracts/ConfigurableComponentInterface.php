<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;

interface ConfigurableComponentInterface
{
    /**
     * Returns the component's renderer-neutral settings Ui.
     *
     * Return `null` if the component has no settings Ui.
     */
    public function settingsUi(UiContext $context = new UiContext): ?Ui;

    /**
     * Returns the list of settings attribute names.
     *
     * By default, this method returns all public non-static properties that weren’t defined by an abstract class,
     * so subclasses of concrete components retain their parents’ settings.
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
