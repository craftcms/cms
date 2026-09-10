<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Field;

use CraftCms\Cms\Field\BaseRelationField as CoreBaseRelationField;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Support\Html;
use CraftCms\Yii2Adapter\Field\Concerns\LegacyFieldControl;
use CraftCms\Yii2Adapter\Field\Concerns\LegacyFieldHtml;
use CraftCms\Yii2Adapter\Field\Concerns\LegacyRelationFieldSettings;
use CraftCms\Yii2Adapter\Field\Contracts\LegacyField;
use CraftCms\Yii2Adapter\Form\Concerns\LegacySettingsForm;
use CraftCms\Yii2Adapter\Form\Contracts\LegacySettingsComponent;

abstract class BaseRelationField extends CoreBaseRelationField implements LegacyField, LegacySettingsComponent
{
    use LegacyFieldControl;
    use LegacyFieldHtml;
    use LegacyRelationFieldSettings {
        getSettingsHtml as private legacyRelationSettingsHtml;
    }
    use LegacySettingsForm {
        settingsForm as private legacySettingsForm;
    }

    public function settingsForm(FormContext $context = new FormContext()): Form
    {
        return $this->legacySettingsForm($context) ?? Form::make();
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
