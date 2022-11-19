<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\components;

use Craft;
use craft\ui\attributes\AsTwigComponent;
use InvalidArgumentException;

#[AsTwigComponent('input:text')]
class InputText extends Input
{
    /**
     * @inheritdoc
     */
    public string $type = 'text';

    /**
     * Directionality of the input (`ltr` or `rtl`)
     *
     * @var string|null
     */
    public ?string $orientation = null;

    /**
     * SiteID input should respect
     *
     * @var int|null
     */
    public ?int $siteId = null;

    public function mount(string $orientation = null, int $siteId = null)
    {
        $siteId = Craft::$app->getIsMultiSite() && $siteId ? $siteId : null;
        if ($siteId) {
            $site = Craft::$app->getSites()->getSiteById($siteId);
            if (!$site) {
                throw new InvalidArgumentException("Invalid site ID: $siteId");
            }
        } else {
            $site = null;
        }

        $this->orientation = $orientation ?? ($site ? $site->getLocale() : Craft::$app->getLocale())->getOrientation();
    }
}
