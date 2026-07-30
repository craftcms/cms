<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Concerns;

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

        // Is there a APP_URL environment variable set that is not just Laravel's default?
        if (($envValue = Env::get('APP_URL')) && ($envValue !== 'http://localhost')) {
            return $envValue;
        }

        // If this is a console request, give up now
        if (app()->runningInConsole()) {
            return null;
        }

        return request()->schemeAndHttpHost();
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

    /** @return array<string, mixed>|null */
    private function primarySiteConfig(): ?array
    {
        return once(function (): ?array {
            $sites = app(ProjectConfig::class)->get('sites', true);

            if (! is_array($sites)) {
                return null;
            }

            foreach ($sites as $site) {
                if (is_array($site) && ($site['primary'] ?? false)) {
                    return $site;
                }
            }

            return null;
        });
    }
}
