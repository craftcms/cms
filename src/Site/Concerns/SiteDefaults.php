<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Concerns;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Env;

trait SiteDefaults
{
    protected function defaultSiteName(): ?string
    {
        // Is there a project.yaml that defines a primary site?
        $primarySite = $this->primarySiteConfig();
        if (! empty($primarySite['name'])) {
            return $primarySite['name'];
        }

        // If this is a console request, give up now
        if (app()->runningInConsole()) {
            return null;
        }

        // Come up with something based on the server name
        $server = request()->host();
        $words = preg_split('/[\-_\.]+/', $server);
        array_pop($words);

        return implode(' ', array_map(ucfirst(...), $words));
    }

    protected function defaultSiteUrl(): ?string
    {
        // Is there a project.yaml that defines a primary site with a base URL?
        $primarySite = $this->primarySiteConfig();
        if (! empty($primarySite['baseUrl'])) {
            return $primarySite['baseUrl'];
        }

        // Is there a APP_URL environment variable set?
        if ($envValue = Env::get('APP_URL')) {
            return $envValue;
        }

        // If this is a console request, give up now
        if (app()->runningInConsole()) {
            return null;
        }

        // Return the URL to the web directory
        return Aliases::get('@web');
    }

    protected function defaultSiteLanguage(): string
    {
        // Is there a project.yaml that defines a primary site?
        $primarySite = $this->primarySiteConfig();
        if (! empty($primarySite['language'])) {
            return $primarySite['language'];
        }

        return 'en';
    }

    private function primarySiteConfig(): ?array
    {
        return once(fn () => collect(
            resolve(ProjectConfig::class)->get('sites', true) ?? []
        )->firstWhere('primary', true));
    }
}
