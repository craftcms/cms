<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use craft\errors\SiteNotFoundException;
use craft\models\Site;
use CraftCms\Cms\Database\Queries\Exceptions\QueryAbortedException;
use InvalidArgumentException;

/**
 * @mixin \CraftCms\Cms\Database\Queries\ElementQuery
 *
 * @internal
 */
trait QueriesSites
{
    /**
     * @var mixed The site ID(s) that the elements should be returned in, or `'*'` if elements
     *            should be returned in all supported sites.
     *
     * @used-by site()
     * @used-by siteId()
     */
    public mixed $siteId = null;

    protected function initQueriesSites(): void
    {
        $this->beforeQuery(function () {
            // Make sure the siteId param is set
            try {
                if (! $this->elementType::isLocalized()) {
                    // The criteria *must* be set to the primary site ID
                    $this->siteId = \Craft::$app->getSites()->getPrimarySite()->id;
                } else {
                    $this->normalizeSiteId();
                }
            } catch (SiteNotFoundException $e) {
                // Fail silently if Craft isn't installed yet or is in the middle of updating
                if (\Craft::$app->getIsInstalled() && ! \Craft::$app->getUpdates()->getIsCraftUpdatePending()) {
                    throw $e;
                }

                throw new QueryAbortedException($e->getMessage(), 0, $e);
            }

            if (\Craft::$app->getIsMultiSite(false, true)) {
                $this->subQuery->where('elements_sites.siteId', $this->siteId);
            }
        });
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException if $value is invalid
     *
     * @uses $siteId
     */
    public function site($value): static
    {
        if ($value === null) {
            $this->siteId = null;
        } elseif ($value === '*') {
            $this->siteId = \Craft::$app->getSites()->getAllSiteIds();
        } elseif ($value instanceof Site) {
            $this->siteId = $value->id;
        } elseif (is_string($value)) {
            $this->siteId = \Craft::$app->getSites()->getSiteByHandle($value)?->id ?? throw new InvalidArgumentException('Invalid site handle: '.$value);
        } else {
            if ($not = (strtolower((string) reset($value)) === 'not')) {
                array_shift($value);
            }

            $this->siteId = [];

            foreach (\Craft::$app->getSites()->getAllSites() as $site) {
                if (in_array($site->handle, $value, true) === ! $not) {
                    $this->siteId[] = $site->id;
                }
            }

            if (empty($this->siteId)) {
                throw new InvalidArgumentException('Invalid site param: ['.($not ? 'not, ' : '').implode(', ',
                    $value).']');
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $siteId
     */
    public function siteId($value): static
    {
        if (is_array($value) && strtolower((string) reset($value)) === 'not') {
            array_shift($value);

            $this->siteId = [];

            foreach (\Craft::$app->getSites()->getAllSites() as $site) {
                if (! in_array($site->id, $value)) {
                    $this->siteId[] = $site->id;
                }
            }

            return $this;
        }

        $this->siteId = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return static
     *
     * @uses $siteId
     */
    public function language($value): self
    {
        if (is_string($value)) {
            $sites = \Craft::$app->getSites()->getSitesByLanguage($value);

            if (empty($sites)) {
                throw new InvalidArgumentException("Invalid language: $value");
            }

            $this->siteId = array_map(fn (Site $site) => $site->id, $sites);

            return $this;
        }

        if ($not = (strtolower((string) reset($value)) === 'not')) {
            array_shift($value);
        }

        $this->siteId = [];

        foreach (\Craft::$app->getSites()->getAllSites() as $site) {
            if (in_array($site->language, $value, true) === ! $not) {
                $this->siteId[] = $site->id;
            }
        }

        if (empty($this->siteId)) {
            throw new InvalidArgumentException('Invalid language param: ['.($not ? 'not, ' : '').implode(', ',
                $value).']');
        }

        return $this;
    }

    /**
     * Normalizes the siteId param value.
     */
    private function normalizeSiteId(): void
    {
        $sitesService = \Craft::$app->getSites();
        if (! $this->siteId) {
            // Default to the current site
            $this->siteId = $sitesService->getCurrentSite()->id;
        } elseif ($this->siteId === '*') {
            $this->siteId = $sitesService->getAllSiteIds();
        } elseif (is_numeric($this->siteId) || Arr::isNumeric($this->siteId)) {
            // Filter out any invalid site IDs
            $siteIds = Collection::make((array) $this->siteId)
                ->filter(fn ($siteId) => $sitesService->getSiteById($siteId, true) !== null)
                ->all();
            if (empty($siteIds)) {
                throw new QueryAbortedException;
            }
            $this->siteId = is_array($this->siteId) ? $siteIds : reset($siteIds);
        }
    }
}
