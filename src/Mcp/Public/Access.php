<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Public;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Mcp\PublicElementTypes;
use CraftCms\Cms\Mcp\Settings;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\User\Data\UserGroup;
use InvalidArgumentException;

/**
 * @since 6.0.0
 */
readonly class Access
{
    public const int DefaultLimit = 25;

    public const int MaxLimit = 100;

    public function __construct(
        private Settings $settings,
        private PublicElementTypes $elementTypes,
    ) {}

    public function enabled(): bool
    {
        return $this->settings->publicEnabled;
    }

    public function route(): string
    {
        return ltrim($this->settings->publicRoute, '/');
    }

    public function approved(string $category, string $identity): bool
    {
        $approved = match ($category) {
            'tools' => $this->settings->publicTools,
            'resources' => $this->settings->publicResources,
            'resourceTemplates' => $this->settings->publicResourceTemplates,
            'prompts' => $this->settings->publicPrompts,
            default => throw new InvalidArgumentException("Unknown public MCP approval category [$category]."),
        };

        return in_array($identity, $approved, true);
    }

    /** @return list<string> */
    public function handles(string $scope): array
    {
        return match ($scope) {
            'sites' => $this->settings->publicSiteHandles,
            'sections' => $this->settings->publicSectionHandles,
            'nestedEntryFields' => $this->settings->publicNestedEntryFieldHandles,
            'volumes' => $this->settings->publicVolumeHandles,
            'userGroups' => $this->settings->publicUserGroupHandles,
            default => throw new InvalidArgumentException("Unknown public MCP scope [$scope]."),
        };
    }

    /** @return list<string> */
    public function elementTypes(): array
    {
        $types = [];

        foreach ($this->settings->publicElementTypes as $name) {
            $type = $this->elementTypes->find($name);

            if ($type === null) {
                throw new InvalidArgumentException("Unknown public MCP element type [$name].");
            }

            if (! $type->isScopeControlled()) {
                $types[$type->name] = $type->name;
            }
        }

        return array_values($types);
    }

    /** @return list<Site|Section|ElementContainerFieldInterface|Volume|UserGroup> */
    public function models(string $scope): array
    {
        return collect($this->handles($scope))
            ->map(fn (string $handle) => $this->resolveHandle($scope, $handle))
            ->all();
    }

    /** @return list<int> */
    public function ids(string $scope): array
    {
        return collect($this->models($scope))
            ->map(static fn (object $model): int => $model->id)
            ->all();
    }

    /** @return array{drafts: bool, revisions: bool, inactive: bool} */
    public function elementFlags(): array
    {
        return [
            'drafts' => $this->settings->publicAllowDrafts,
            'revisions' => $this->settings->publicAllowRevisions,
            'inactive' => $this->settings->publicAllowInactive,
        ];
    }

    private function resolveHandle(string $scope, string $handle): Site|Section|ElementContainerFieldInterface|Volume|UserGroup
    {
        $model = match ($scope) {
            'sites' => Sites::getSiteByHandle($handle, true),
            'sections' => Sections::getSectionByHandle($handle),
            'nestedEntryFields' => $this->nestedEntryField($handle),
            'volumes' => Volumes::getVolumeByHandle($handle),
            'userGroups' => UserGroups::getGroupByHandle($handle),
            default => throw new InvalidArgumentException("Unknown public MCP scope [$scope]."),
        };

        if ($model === null) {
            throw new InvalidArgumentException("Unknown public MCP $scope handle [$handle].");
        }

        return $model;
    }

    private function nestedEntryField(string $handle): ?ElementContainerFieldInterface
    {
        $field = Fields::getFieldByHandle($handle);

        if (! $field instanceof ElementContainerFieldInterface) {
            return null;
        }

        return Fields::getNestedEntryFieldTypes()->contains($field::class) ? $field : null;
    }
}
