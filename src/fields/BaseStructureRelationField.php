<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\ElementHelper;
use craft\helpers\ArrayHelper;

/**
 * Categories represents a Categories field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class BaseStructureRelationField extends BaseRelationField
{
    // Properties
    // =========================================================================

    /**
     * @var int|null Branch limit
     */
    public $branchLimit;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->allowMultipleSources = false;
        $this->sortable = false;
        $this->allowLimit = false;
        $this->inputJsClass = 'Craft.StructureSelectInput';
        $this->settingsTemplate = '_components/fieldtypes/structureelementfieldsettings';
    }

    /**
     * @inheritdoc
     */
    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'branchLimit';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if (is_array($value)) {
            /** @var ElementInterface[] $elements */
            $elements = static::elementType()::find()
                ->siteId($this->targetSiteId($element))
                ->id(array_values(array_filter($value)))
                ->anyStatus()
                ->all();

            // Fill in any gaps
            $elementsService = Craft::$app->getCategories();
            $elementsService->fillGapsInCategories($elements);

            // Enforce the branch limit
            if ($this->branchLimit) {
                $elementsService->applyBranchLimitToCategories($elements, $this->branchLimit);
            }

            $value = ArrayHelper::getColumn($elements, 'id');
        }

        return parent::normalizeValue($value, $element);
    }

    /**
     * @inheritdoc
     */
    protected function inputTemplateVariables($value = null, ElementInterface $element = null): array
    {
        $variables = parent::inputTemplateVariables($value, $element);
        $variables['branchLimit'] = $this->branchLimit;
        $variables['structure'] = true;

        return $variables;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        // Make sure the field is set to a valid element source
        if ($this->source) {
            $source = ElementHelper::findSource(static::elementType(), $this->source, 'field');
        }

        if (empty($source)) {
            return '<p class="error">' . Craft::t('app', 'This field is not set to a valid source.') . '</p>';
        }

        return parent::getInputHtml($value, $element);
    }
}
