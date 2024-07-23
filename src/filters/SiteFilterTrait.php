<?php

namespace craft\filters;

use Craft;
use craft\models\Site;
use yii\base\InvalidArgumentException;

trait SiteFilterTrait
{
    private null|array $siteIds = null;

    protected function isActive(mixed $action): bool
    {
        if (Craft::$app->getRequest()->isCpRequest || !$this->isCurrentSiteActive()) {
            return false;
        }

        return parent::isActive($action);
    }

    protected function setSite(null|array|int|string|Site $value): void
    {
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
        return $this->siteIds === null || in_array(Craft::$app->getSites()->getCurrentSite()->id, $this->siteIds, true);
    }
}
