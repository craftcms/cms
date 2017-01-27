<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\web\assets\positionselect\PositionSelectAsset;
use yii\db\Schema;

/**
 * PositionSelect represents a Position Select field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class PositionSelect extends Field
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Position Select');
    }

    /**
     * Returns the position options.
     *
     * @return array
     */
    private static function _getOptions(): array
    {
        return [
            'left' => Craft::t('app', 'Left'),
            'center' => Craft::t('app', 'Center'),
            'right' => Craft::t('app', 'Right'),
            'full' => Craft::t('app', 'Full'),
            'drop-left' => Craft::t('app', 'Drop-left'),
            'drop-right' => Craft::t('app', 'Drop-right'),
        ];
    }

    // Properties
    // =========================================================================

    /**
     * @var string[]|null The position options that should be shown in the field
     */
    public $options;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->options === null) {
            $this->options = array_keys(self::_getOptions());
        } else {
            $this->options = array_values(array_filter($this->options));
        }
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_STRING.'(100)';
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/PositionSelect/settings',
            [
                'field' => $this,
                'allOptions' => array_keys(self::_getOptions()),
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        if (empty($this->options)) {
            return '<p><em>'.Craft::t('app', 'No options selected.').'</em></p>';
        }

        $view = Craft::$app->getView();

        $view->registerAssetBundle(PositionSelectAsset::class);

        $id = $view->formatInputId($this->handle);
        $view->registerJs('new PositionSelectInput("'.Craft::$app->getView()->namespaceInputId($id).'");');

        if (!$value && !empty($this->options)) {
            $value = $this->options[0];
        }

        return $view->renderTemplate('_components/fieldtypes/PositionSelect/input',
            [
                'id' => $id,
                'name' => $this->handle,
                'value' => $value,
                'options' => $this->options,
                'allOptions' => self::_getOptions(),
            ]);
    }
}
