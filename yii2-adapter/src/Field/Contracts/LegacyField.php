<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Field\Contracts;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Yii2Adapter\Form\Contracts\LegacySettingsComponent;

interface LegacyField extends FieldInterface, LegacySettingsComponent
{
    public function getInputHtml(mixed $value, ?ElementInterface $element): string;

    public function getStaticHtml(mixed $value, ElementInterface $element): string;
}
