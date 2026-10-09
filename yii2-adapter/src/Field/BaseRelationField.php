<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Field;

use CraftCms\Cms\Field\BaseRelationField as CoreBaseRelationField;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Yii2Adapter\Field\Concerns\LegacyFieldControl;
use CraftCms\Yii2Adapter\Field\Concerns\LegacyFieldHtml;
use CraftCms\Yii2Adapter\Field\Concerns\LegacyRelationFieldSettings;
use CraftCms\Yii2Adapter\Field\Contracts\LegacyField;
use CraftCms\Yii2Adapter\Ui\Concerns\LegacySettingsUi;
use CraftCms\Yii2Adapter\Ui\Contracts\LegacySettingsComponent;

abstract class BaseRelationField extends CoreBaseRelationField implements LegacyField, LegacySettingsComponent
{
    use LegacyFieldControl;
    use LegacyFieldHtml;
    use LegacyRelationFieldSettings {
        getSettingsHtml as private legacyRelationSettingsHtml;
    }
    use LegacySettingsUi {
        settingsUi as private legacySettingsUi;
    }

    public function settingsUi(UiContext $context = new UiContext()): Ui
    {
        return $this->legacySettingsUi($context) ?? Ui::make();
    }

    public function getSettingsHtml(): string
    {
        return $this->legacyRelationSettingsHtml();
    }

    public function getReadOnlySettingsHtml(): string
    {
        return Html::disableInputs(fn() => $this->getSettingsHtml());
    }
}
