<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\filters;

use Craft;
use craft\models\Site;
use yii\base\InvalidArgumentException;

/**
 * Trait to make a filter site-aware.
 *
 * @property-write int|string|Site|array<int|string|Site> $site
 * @see https://www.yiiframework.com/doc/api/2.0/yii-filters-cors
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
trait SiteFilterTrait
{
    use ConditionalFilterTrait {
        isActive as conditionalFilterTraitIsActive;
    }

    private null|array $siteIds = null;

    protected function isActive(mixed $action): bool
    {
        if (!$this->conditionalFilterTraitIsActive($action)) {
            return false;
        }

        return $this->isCurrentSiteActive();
    }

    protected function setSite(null|array|int|string|Site $value): void
    {
        // if the app is not fully initialised, ensure Craft's edition is set
        // @see https://github.com/craftcms/cms/issues/16288 for details on why this is needed
        if (!Craft::$app->getIsInitialized()) {
            Craft::$app->ensureEdition();
        }

        $this->siteIds = match (true) {
            $value === null, $value === '*' => null,
            is_array($value) => array_map(fn($site) => $this->getSiteId($site), $value),
            default => [$this->getSiteId($value)],
        };
    }

    private function getSiteId(int|string|Site $value): int
    {
        if (is_string($value)) {
            $site = Craft::$app->getSites()->getSiteByHandle($value);

            if ($site === null) {
                throw new InvalidArgumentException("Invalid site handle: $value");
            }

            return $site->id;
        } elseif ($value instanceof Site) {
            return $value->id;
        }

        return $value;
    }

    private function isCurrentSiteActive(): bool
    {
        if (!Craft::$app->getRequest()->getIsSiteRequest()) {
            return false;
        }

        return $this->siteIds === null || in_array(Craft::$app->getSites()->getCurrentSite()->id, $this->siteIds, true);
    }
}
