<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Form\Concerns;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use function CraftCms\Cms\t;

/** @phpstan-require-extends \CraftCms\Cms\Condition\BaseElementSelectConditionRule */
trait LegacyElementSelectConditionRule
{
    use LegacyConditionRuleForm;

    protected function inputHtml(): string
    {
        if ($this->getCondition()->forProjectConfig) {
            $value = $this->getElementIds(false);
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            $type = $this->elementType()::displayName();

            return FormFields::textFieldHtml([
                'textExpanderTriggers' => SelectOptions::getEnvTextExpanderTriggers(
                    filter: fn($value) => filter_var($value, FILTER_VALIDATE_INT) !== false && (int) $value > 0,
                ),
                'required' => true,
                'id' => 'elementIds',
                'class' => 'code',
                'name' => 'elementIds',
                'value' => $value,
                'tip' => $this->allowMultiple()
                    ? t('Type `$` to choose an environment variable, or enter a Twig template that outputs comma-separated IDs.')
                    : t('Type `$` to choose an environment variable, or enter a Twig template that outputs an ID.'),
                'placeholder' => $this->allowMultiple()
                    ? t('{type} ID(s)', ['type' => $type])
                    : t('{type} ID', ['type' => $type]),
            ]);
        }

        return FormFields::elementSelectHtml($this->elementSelectConfig());
    }

    /**
     * Defines the element select config.
     *
     * @return array<string, mixed>
     */
    protected function elementSelectConfig(): array
    {
        $elements = $this->_elements();

        return [
            'name' => 'elementIds',
            'elements' => $elements,
            'elementType' => $this->elementType(),
            'sources' => $this->sources(),
            'criteria' => $this->criteria(),
            'condition' => $this->selectionCondition(),
            'single' => !$this->allowMultiple(),
        ];
    }

    /**
     * @return ElementInterface[]
     */
    private function _elements(): array
    {
        $elementIds = $this->getElementIds();

        if (empty($elementIds)) {
            return [];
        }

        return $this->elementType()::find()
            ->site('*')
            ->preferSites(array_filter([app(RequestedSite::class)->get()?->id]))
            ->unique()
            ->id($elementIds)
            ->status(null)
            ->limit($this->allowMultiple() ? null : 1)
            ->all();
    }
}
