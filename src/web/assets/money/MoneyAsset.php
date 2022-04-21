<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\money;

use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\View;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use yii\widgets\MaskedInputAsset;

/**
 * Asset bundle for Money field
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class MoneyAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        CpAsset::class,
        MaskedInputAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'Money.js',
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/Money.css',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);

        $js = <<<JS
window.Craft.CurrencySubUnits = {$this->_getCurrencySubUnits()};
JS;
        $view->registerJs($js, View::POS_HEAD);
    }

    /**
     * @return string
     */
    private function _getCurrencySubUnits(): string
    {
        $currencies = new ISOCurrencies();
        $subUnitsByCurrencyCode = ArrayHelper::map(iterator_to_array($currencies), static function(Currency $currency) {
            return $currency->getCode();
        }, static function(Currency $currency) use ($currencies) {
            return $currencies->subunitFor($currency);
        });

        return Json::encode($subUnitsByCurrencyCode);
    }
}
