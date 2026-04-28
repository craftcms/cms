<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\LegacyAssets;

use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\HtmlStack;
use Money\Currencies\ISOCurrencies;
use Money\Currency;

use function CraftCms\Cms\craftAsset;

/**
 * @deprecated
 *
 * @internal
 */
class MoneyAsset implements LegacyAssetInterface
{
    public array $depends = [
        CpAsset::class,
        InputmaskAsset::class,
    ];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('legacy/money/dist/Money.js'));
        $htmlStack->cssFile(craftAsset('legacy/money/dist/css/Money.css'));
        $htmlStack->js(<<<JS
        window.Craft.CurrencySubUnits = {$this->_getCurrencySubUnits()};
        JS, Position::Head);
    }

    private function _getCurrencySubUnits(): string
    {
        $currencies = new ISOCurrencies;
        $subUnitsByCurrencyCode = Arr::mapWithKeys(
            iterator_to_array($currencies),
            static fn (Currency $currency) => [$currency->getCode() => $currencies->subunitFor($currency)],
        );

        return Json::encode($subUnitsByCurrencyCode);
    }
}
