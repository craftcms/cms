<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Field;

use CraftCms\Cms\Field\Field as CoreField;
use CraftCms\Cms\Support\Html;
use CraftCms\Yii2Adapter\Field\Concerns\LegacyFieldControl;
use CraftCms\Yii2Adapter\Field\Concerns\LegacyFieldHtml;
use CraftCms\Yii2Adapter\Field\Contracts\LegacyField;
use CraftCms\Yii2Adapter\Form\Concerns\LegacySettingsForm;
use CraftCms\Yii2Adapter\Form\Contracts\LegacySettingsComponent;

abstract class Field extends CoreField implements LegacyField, LegacySettingsComponent
{
    use LegacyFieldControl;
    use LegacyFieldHtml;
    use LegacySettingsForm;

    public function getSettingsHtml(): ?string
    {
        return null;
    }

    public function getReadOnlySettingsHtml(): ?string
    {
        return Html::disableInputs(fn() => $this->getSettingsHtml());
    }
}
