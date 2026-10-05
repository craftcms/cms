<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\ProjectConfig\ProjectConfig as ProjectConfigService;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 6.0.0
 */
readonly class ProjectConfig
{
    public function __construct(private ProjectConfigService $projectConfig) {}

    /** @return array{path: string|null, external: bool, value: mixed} */
    #[McpTool(
        name: 'project-config.get',
        description: 'Gets a loaded or external Craft CMS project config value by path. Omit the path to return the full config.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function get(?string $path = null, bool $external = false): array
    {
        $value = $this->projectConfig->get($path, $external);

        return [
            'path' => $path,
            'external' => $external,
            'value' => is_array($value) ? ProjectConfigHelper::cleanupConfig($value) : $value,
        ];
    }

    /** @return array{status: array<string, mixed>} */
    #[McpTool(
        name: 'project-config.status',
        description: 'Returns Craft CMS project config status, including pending YAML changes and write issues.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function status(?string $path = null): array
    {
        $schemaIssues = [];
        $pendingChanges = $this->projectConfig->getPendingChanges();

        return ['status' => [
            'path' => $path,
            'externalConfigExists' => $this->projectConfig->getDoesExternalConfigExist(),
            'changesPending' => $this->changesPending($path, $pendingChanges),
            'pendingChanges' => $pendingChanges,
            'isApplyingExternalChanges' => $this->projectConfig->isApplyingExternalChanges(),
            'hadFileWriteIssues' => $this->projectConfig->getHadFileWriteIssues(),
            'schemaVersionsCompatible' => $this->projectConfig->getAreConfigSchemaVersionsCompatible($schemaIssues),
            'schemaIssues' => $schemaIssues,
            'appliedChanges' => $this->projectConfig->getAppliedChanges(),
        ]];
    }

    /** @return array{invert: bool, diff: string} */
    #[McpTool(
        name: 'project-config.diff',
        description: 'Returns a diff of pending Craft CMS project config YAML changes.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function diff(bool $invert = false): array
    {
        return [
            'invert' => $invert,
            'diff' => ProjectConfigHelper::diff($invert),
        ];
    }

    /** @return array{applied: true, appliedChanges: list<array{added?: array<string, mixed>, removed?: array<string, mixed>, message?: string}>} */
    #[McpTool(
        name: 'project-config.apply',
        description: 'Applies external Craft CMS project config YAML changes to the loaded project config.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function apply(): array
    {
        $this->projectConfig->applyExternalChanges();

        return [
            'applied' => true,
            'appliedChanges' => $this->projectConfig->getAppliedChanges(),
        ];
    }

    /** @return array{written: true, forced: bool, hadFileWriteIssues: bool} */
    #[McpTool(
        name: 'project-config.write',
        description: 'Writes Craft CMS project config YAML files from the loaded project config.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function write(bool $force = false): array
    {
        $this->projectConfig->writeYamlFiles($force);

        return [
            'written' => true,
            'forced' => $force,
            'hadFileWriteIssues' => $this->projectConfig->getHadFileWriteIssues(),
        ];
    }

    /**
     * @param  array{newItems: list<string>, removedItems: list<string>, changedItems: list<string>}  $pendingChanges
     */
    private function changesPending(?string $path, array $pendingChanges): bool
    {
        if ($path === null) {
            return array_filter($pendingChanges) !== [];
        }

        return ProjectConfigHelper::encodeValueAsString($this->projectConfig->get($path))
            !== ProjectConfigHelper::encodeValueAsString($this->projectConfig->get($path, true));
    }
}
