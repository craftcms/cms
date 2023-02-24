<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\fields\data\MultiOptionsFieldData;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;

/**
 * MultiSelect represents a Multi-select field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class MultiSelect extends BaseOptionsField
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Multi-select');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return sprintf('\\%s', MultiOptionsFieldData::class);
    }

    /**
     * @inheritdoc
     */
    protected bool $multi = true;

    /**
     * @inheritdoc
     */
    protected bool $optgroups = true;

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        /** @var MultiOptionsFieldData $value */
        if (ArrayHelper::contains($value, 'valid', false, true)) {
            Craft::$app->getView()->setInitialDeltaValue($this->handle, null);
        }

        $id = $this->getInputId();

        $view = Craft::$app->getView();
        $view->registerJsWithVars(fn($id) => <<<JS
$('#' + $id).selectize({
  plugins: ['remove_button'],
  dropdownParent: 'body',
});
JS, [
            $view->namespaceInputId($id),
        ]);

        return Cp::multiSelectHtml([
            'id' => $id,
            'describedBy' => $this->describedBy,
            'class' => 'selectize',
            'name' => $this->handle,
            'values' => $this->encodeValue($value),
            'options' => $this->translatedOptions(true, $value, $element),
            'inputAttributes' => [
                'style' => [
                    'display' => 'none', // Hide it before selectize does its thing
                ],
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function optionsSettingLabel(): string
    {
        return Craft::t('app', 'Multi-select Options');
    }
}
