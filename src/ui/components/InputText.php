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

    /**
     * Value of the input
     *
     * @var string|null
     */
    public ?string $value = null;

    public function value(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function mount(
        string $orientation = null,
    ) {
        $siteId = Craft::$app->getIsMultiSite() && $this->siteId ? $this->siteId : null;
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
