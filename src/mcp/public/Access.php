<?php

declare(strict_types=1);

namespace craft\mcp\public;

use Craft;

use craft\base\ElementContainerFieldInterface;
use craft\mcp\PublicElementTypes;
use craft\mcp\Settings;
use craft\models\Section;
use craft\models\Site;
use craft\models\UserGroup;
use craft\models\Volume;
use InvalidArgumentException;

/**
 * @since 5.12.0
 */
readonly class Access
{
    public const DefaultLimit = 25;

    public const MaxLimit = 100;

    public function __construct(
        private Settings $settings,
        private PublicElementTypes $elementTypes,
    ) {
    }

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

            if (!$type->isScopeControlled()) {
                $types[$type->name] = $type->name;
            }
        }

        return array_values($types);
    }

    /** @return list<Site|Section|ElementContainerFieldInterface|Volume|UserGroup> */
    public function models(string $scope): array
    {
        return collect($this->handles($scope))
            ->map(fn(string $handle) => $this->resolveHandle($scope, $handle))
            ->all();
    }

    /** @return list<int> */
    public function ids(string $scope): array
    {
        return collect($this->models($scope))
            ->map(static fn(object $model): int => $model->id)
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
            'sites' => Craft::$app->getSites()->getSiteByHandle($handle, true),
            'sections' => Craft::$app->getEntries()->getSectionByHandle($handle),
            'nestedEntryFields' => $this->nestedEntryField($handle),
            'volumes' => Craft::$app->getVolumes()->getVolumeByHandle($handle),
            'userGroups' => Craft::$app->getUserGroups()->getGroupByHandle($handle),
            default => throw new InvalidArgumentException("Unknown public MCP scope [$scope]."),
        };

        if ($model === null) {
            throw new InvalidArgumentException("Unknown public MCP $scope handle [$handle].");
        }

        return $model;
    }

    private function nestedEntryField(string $handle): ?ElementContainerFieldInterface
    {
        $field = Craft::$app->getFields()->getFieldByHandle($handle);

        if (!$field instanceof ElementContainerFieldInterface) {
            return null;
        }

        return in_array($field::class, Craft::$app->getFields()->getNestedEntryFieldTypes(), true) ? $field : null;
    }
}
