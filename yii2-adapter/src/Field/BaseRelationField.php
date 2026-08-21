<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Field;

use CraftCms\Cms\Field\BaseRelationField as CoreBaseRelationField;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Yii2Adapter\Field\Concerns\LegacyFieldControl;
use CraftCms\Yii2Adapter\Field\Concerns\LegacyFieldHtml;
use CraftCms\Yii2Adapter\Field\Contracts\LegacyField;
use CraftCms\Yii2Adapter\Form\Concerns\LegacySettingsForm;
use CraftCms\Yii2Adapter\Form\Contracts\LegacySettingsComponent;

use function CraftCms\Cms\template;

abstract class BaseRelationField extends CoreBaseRelationField implements LegacyField, LegacySettingsComponent
{
    use LegacyFieldControl;
    use LegacyFieldHtml;
    use LegacySettingsForm {
        settingsForm as private legacySettingsForm;
    }

    public function settingsForm(FormContext $context = new FormContext()): Form
    {
        return $this->legacySettingsForm($context) ?? Form::make();
    }

    public function getSettingsHtml(): string
    {
        $variables = $this->settingsTemplateVariables();

        HtmlStack::jsWithVars(fn($args) => <<<JS
new Craft.ElementFieldSettings(...$args)
JS, [
            [
                $this->allowMultipleSources,
                InputNamespace::namespaceId('maintain-hierarchy-field'),
                InputNamespace::namespaceId($this->allowMultipleSources ? 'sources-field' : 'source-field'),
                InputNamespace::namespaceId('branch-limit-field'),
                InputNamespace::namespaceId('min-relations-field'),
                InputNamespace::namespaceId('max-relations-field'),
                InputNamespace::namespaceId('default-placement-field'),
                InputNamespace::namespaceId('viewMode-field'),
            ],
        ]);

        return template($this->settingsTemplate, $variables);
    }

    public function getReadOnlySettingsHtml(): string
    {
        return Html::disableInputs(fn() => $this->getSettingsHtml());
    }
}
