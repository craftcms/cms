<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Form\Contracts;

use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;

interface LegacySettingsComponent extends ConfigurableComponentInterface
{
    public function getSettingsHtml(): ?string;

    public function getReadOnlySettingsHtml(): ?string;
}
