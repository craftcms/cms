<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Sites;
use Illuminate\Container\Attributes\Scoped;

#[Scoped]
class RequestedSite
{
    private Site|false|null $requestedSite = null;

    public function __construct(
        private readonly Sites $sites,
    ) {}

    public function get(): ?Site
    {
        if (isset($this->requestedSite)) {
            return $this->requestedSite ?: null;
        }

        $editableSiteIds = $this->sites->getEditableSiteIds()->all();

        if (empty($editableSiteIds)) {
            $this->requestedSite = false;

            return null;
        }

        // Testbench reports an HTTP test as running in console, so the console
        // guard on its own would have the CP ignore `?site=` in every test.
        if (
            (! app()->runningInConsole() || app()->runningUnitTests()) &&
            ($handle = request()->query('site')) !== null &&
            ($site = $this->sites->getSiteByHandle($handle, true)) !== null &&
            in_array($site->id, $editableSiteIds)
        ) {
            $this->requestedSite = $site;

            return $this->requestedSite;
        }

        $this->requestedSite = $this->sites->getCurrentSite();

        if (! in_array($this->requestedSite->id, $editableSiteIds)) {
            $this->requestedSite = $this->sites->getSiteById($editableSiteIds[0]);
        }

        return $this->requestedSite ?: null;
    }

    public function reset(): void
    {
        $this->requestedSite = null;
    }
}
