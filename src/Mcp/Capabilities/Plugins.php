<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\Plugin\Plugins as PluginService;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\Update\Updates;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 6.0.0
 */
readonly class Plugins
{
    public function __construct(
        private Composer $composer,
        private GeneralConfig $generalConfig,
        private PluginService $plugins,
        private Updates $updates,
    ) {}

    /** @return array{count: int, updatesChecked: bool, plugins: list<array<string, mixed>>} */
    #[McpTool(
        name: 'plugins.list',
        description: 'Lists Craft CMS plugins with their version, enabled state, license status, and available updates.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function list(bool $checkUpdates = false): array
    {
        $pluginUpdates = $checkUpdates || $this->updates->isUpdateInfoCached()
            ? $this->updates->getUpdates()->plugins
            : null;

        $plugins = collect($this->plugins->getComposerPluginInfo() ?? [])
            ->keys()
            ->map(function (mixed $handle) use ($pluginUpdates): array {
                assert(is_string($handle));

                $plugin = $this->serialize($handle);

                if ($pluginUpdates !== null) {
                    $update = $pluginUpdates[$handle] ?? null;
                    $plugin['updateAvailable'] = $update !== null && $update->hasReleases();
                    $plugin['latestVersion'] = $update?->latest()?->version;
                    $plugin['abandoned'] = $update !== null && $update->abandoned;
                }

                return $plugin;
            })
            ->values()
            ->all();

        return [
            'count' => count($plugins),
            'updatesChecked' => $pluginUpdates !== null,
            'plugins' => $plugins,
        ];
    }

    /**
     * @param  string|null  $packageName  Composer package name when the plugin is not already Composer-installed.
     * @param  string|null  $version  Composer version constraint when the plugin is not already Composer-installed.
     * @return array{plugin: array<string, mixed>}|array{composerInstalled: true, handle: string, packageName: string, restartRequired: true, instructions: string, nextToolCall: array{name: 'plugins.install', arguments: array{handle: string, edition?: string}}}
     */
    #[McpTool(
        name: 'plugins.install',
        description: 'Installs a Composer-installed Craft CMS plugin.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function install(
        string $handle,
        ?string $edition = null,
        ?string $packageName = null,
        ?string $version = null,
    ): array {
        if ($this->plugins->getComposerPluginInfo($handle) === null) {
            return $this->installComposerPackage($handle, $edition, $packageName, $version);
        }

        if (! $this->plugins->installPlugin($handle, $edition)) {
            throw new ToolCallException("Plugin [$handle] could not be installed.");
        }

        return ['plugin' => $this->serialize($handle)];
    }

    /** @return array{composerInstalled: true, handle: string, packageName: string, restartRequired: true, instructions: string, nextToolCall: array{name: 'plugins.install', arguments: array{handle: string, edition?: string}}} */
    private function installComposerPackage(
        string $handle,
        ?string $edition,
        ?string $packageName,
        ?string $version,
    ): array {
        if (! $this->generalConfig->allowUpdates) {
            throw new ToolCallException('Plugin installation through Composer is disabled by allowUpdates.');
        }

        if ($packageName === null || trim($packageName) === '' || $version === null || trim($version) === '') {
            throw new ToolCallException('Provide packageName and version to install a plugin that is not already Composer-installed.');
        }

        $this->composer->install([$packageName => $version]);

        return [
            'composerInstalled' => true,
            'handle' => $handle,
            'packageName' => $packageName,
            'restartRequired' => true,
            'instructions' => 'Reconnect to the Craft MCP server so it loads the new Composer dependencies, then make the indicated tool call to complete the Craft plugin installation.',
            'nextToolCall' => [
                'name' => 'plugins.install',
                'arguments' => array_filter([
                    'handle' => $handle,
                    'edition' => $edition,
                ], static fn (?string $value): bool => $value !== null),
            ],
        ];
    }

    /** @return array{plugin: array<string, mixed>} */
    #[McpTool(
        name: 'plugins.enable',
        description: 'Enables an installed Craft CMS plugin.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function enable(string $handle): array
    {
        $this->requireInstalled($handle);

        if (! $this->plugins->enablePlugin($handle)) {
            throw new ToolCallException("Plugin [$handle] could not be enabled.");
        }

        return ['plugin' => $this->serialize($handle)];
    }

    /** @return array{plugin: array<string, mixed>} */
    #[McpTool(
        name: 'plugins.disable',
        description: 'Disables an installed Craft CMS plugin.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function disable(string $handle): array
    {
        $this->requireInstalled($handle);

        if (! $this->plugins->disablePlugin($handle)) {
            throw new ToolCallException("Plugin [$handle] could not be disabled.");
        }

        return ['plugin' => $this->serialize($handle)];
    }

    /** @return array{plugin: array<string, mixed>} */
    #[McpTool(
        name: 'plugins.uninstall',
        description: 'Uninstalls a Craft CMS plugin, removing its data from the database and project config.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function uninstall(string $handle, bool $force = false): array
    {
        $this->requireInstalled($handle);

        try {
            $uninstalled = $this->plugins->uninstallPlugin($handle, $force);
        } catch (InvalidPluginException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }

        if (! $uninstalled) {
            throw new ToolCallException("Plugin [$handle] could not be uninstalled.");
        }

        return ['plugin' => $this->serialize($handle)];
    }

    private function requireInstalled(string $handle): void
    {
        if (! $this->plugins->isPluginInstalled($handle)) {
            throw new ToolCallException("Plugin [$handle] is not installed.");
        }
    }

    /** @return array<string, mixed> */
    private function serialize(string $handle): array
    {
        $composerInfo = $this->plugins->getComposerPluginInfo($handle) ?? [];
        $storedInfo = $this->plugins->getStoredPluginInfo($handle);

        return [
            'handle' => $handle,
            'name' => $composerInfo['name'] ?? $handle,
            'packageName' => $composerInfo['packageName'] ?? null,
            'version' => $composerInfo['version'] ?? null,
            'description' => $composerInfo['description'] ?? null,
            'developer' => $composerInfo['developer'] ?? null,
            'developerUrl' => $composerInfo['developerUrl'] ?? null,
            'documentationUrl' => $composerInfo['documentationUrl'] ?? null,
            'edition' => $storedInfo['edition'] ?? null,
            'isInstalled' => $storedInfo !== null,
            'isEnabled' => $this->plugins->isPluginEnabled($handle),
            'licenseKeyStatus' => $this->plugins->getPluginLicenseKeyStatus($handle)->value,
            'licenseIssues' => $this->plugins->getLicenseIssues($handle),
        ];
    }
}
