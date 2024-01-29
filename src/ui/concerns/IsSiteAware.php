<?php

namespace craft\ui\concerns;

use Craft;
use craft\models\Site;
use yii\base\InvalidArgumentException;

trait IsSiteAware
{
    /**
     * @var int|null The site ID
     */
    protected ?int $siteId = null;

    public function siteId(?int $siteId): static
    {
        $this->siteId = $siteId;
        return $this;
    }

    public function getSiteId(): ?int
    {
        return Craft::$app->getIsMultiSite() ? $this->siteId : null;
    }

    public function getSite(): ?Site
    {
        if ($this->getSiteId()) {
            $site = Craft::$app->getSites()->getSiteById($this->siteId);
            if (!$site) {
                throw new InvalidArgumentException("Invalid site ID: $this->siteId");
            }
            return $site;
        } else {
            return null;
        }
    }

}