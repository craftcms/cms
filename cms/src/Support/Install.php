<?php

namespace CraftCms\Cms\Support;

use Craft;
use CraftCms\Aliases\Facades\Aliases;

/**
 * @since 6.0.0
 */
final class Install
{
    public static function defaultSiteName(): ?string
    {
        // Is there a project.yaml that defines a primary site?
        $primarySite = self::primarySiteConfig();
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

        return implode(' ', array_map('ucfirst', $words));
    }

    public static function defaultSiteUrl(): ?string
    {
        // Is there a project.yaml that defines a primary site with a base URL?
        $primarySite = self::primarySiteConfig();
        if (! empty($primarySite['baseUrl'])) {
            return $primarySite['baseUrl'];
        }

        // Is there a PRIMARY_SITE_URL environment variable set?
        if ($envValue = Env::get('PRIMARY_SITE_URL')) {
            return $envValue;
        }

        // If this is a console request, give up now
        if (app()->runningInConsole()) {
            return null;
        }

        // Return the URL to the web directory
        return Aliases::get('@web');
    }

    public static function defaultSiteLanguage(): string
    {
        // Is there a project.yaml that defines a primary site?
        $primarySite = self::primarySiteConfig();
        if (! empty($primarySite['language'])) {
            return $primarySite['language'];
        }

        return 'en';
    }

    private static function primarySiteConfig(): ?array
    {
        return once(fn () => collect(
            Craft::$app->getProjectConfig()->get('sites', true) ?? []
        )->firstWhere('primary', true));
    }
}
