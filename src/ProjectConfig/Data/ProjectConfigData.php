<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Data;

use CraftCms\Cms\ProjectConfig\Events\AddingItem;
use CraftCms\Cms\ProjectConfig\Events\ItemAdded;
use CraftCms\Cms\ProjectConfig\Events\ItemRemoved;
use CraftCms\Cms\ProjectConfig\Events\ItemUpdated;
use CraftCms\Cms\ProjectConfig\Events\RemovingItem;
use CraftCms\Cms\ProjectConfig\Events\UpdatingItem;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Support\Str;

class ProjectConfigData extends ReadOnlyProjectConfigData
{
    /**
     * @var array Holds the already parsed paths as keys.
     */
    private array $parsedChanges;

    public function __construct(public array $data, public ProjectConfig $projectConfig)
    {
        parent::__construct($data, $projectConfig);

        $this->parsedChanges = [];
    }

    /**
     * Return true if a path has been modified over the lifetime of this project config set.
     */
    public function getHasPathBeenModified(string $path): bool
    {
        return array_key_exists($path, $this->parsedChanges);
    }

    /**
     * Commit changes by firing the appropriate events and updating the appropriate storages.
     */
    public function commitChanges(mixed $oldValue, mixed $newValue, string $path, bool $triggerUpdate = false, ?string $message = null, bool $force = false): void
    {
        if (! $force && ! empty($this->parsedChanges[$path])) {
            return;
        }

        $this->parsedChanges[$path] = true;

        $valueChanged = (
            $triggerUpdate ||
            $this->projectConfig->forceUpdate ||
            ProjectConfigHelper::encodeValueAsString($oldValue) !== ProjectConfigHelper::encodeValueAsString($newValue)
        );

        if ($valueChanged && ! $this->projectConfig->muteEvents) {
            event(match (true) {
                $newValue === null && $oldValue !== null => new RemovingItem($path, $oldValue, $newValue),
                $oldValue === null && $newValue !== null => new AddingItem($path, $oldValue, $newValue),
                default => new UpdatingItem($path, $oldValue, $newValue),
            });
        }

        if (($valueChanged || $force) && ! str_starts_with($path, ProjectConfig::PATH_META_NAMES)) {
            $this->updateContainedProjectConfigNames(pathinfo($path, PATHINFO_EXTENSION), $oldValue, $newValue);
        }

        if ($valueChanged && ! $this->projectConfig->muteEvents) {
            event(match (true) {
                $newValue === null && $oldValue !== null => new ItemRemoved($path, $oldValue, $newValue),
                $oldValue === null && $newValue !== null => new ItemAdded($path, $oldValue, $newValue),
                default => new ItemUpdated($path, $oldValue, $newValue),
            });
        }

        // Mark this path, and any parent paths, as parsed
        $tok = strtok($path, '.');
        $thisPath = '';
        while ($tok !== false) {
            $thisPath .= ($thisPath !== '' ? '.' : '').$tok;
            $this->parsedChanges[$thisPath] = true;
            $tok = strtok('.');
        }

        if ($valueChanged) {
            // Memoize the new config data
            $this->projectConfig->rememberAppliedChanges($path, $oldValue, $newValue, $message);
            $this->setInternal($path, $newValue);

            if ($this->projectConfig->writeYamlAutomatically) {
                $this->projectConfig->updateParsedConfigTimesAfterRequest();
            }
        }
    }

    /**
     * Update the internal data storage.
     *
     * @param  string|string[]  $path
     */
    private function setInternal(string|array $path, mixed $value): void
    {
        if ($value === null) {
            $this->delete($path);
        }

        ProjectConfigHelper::traverseDataArray($this->data, $path, $value);
    }

    /**
     * Delete a path from the internal data storage.
     *
     * @param  string|string[]  $path
     */
    private function delete(string|array $path): mixed
    {
        ProjectConfigHelper::traverseDataArray($this->data, $path, null, true);

        return null;
    }

    private function updateContainedProjectConfigNames(string|int $lastPathSegment, mixed $oldValue, mixed $newValue): void
    {
        // Normalize both values to arrays
        $newValue = is_array($newValue) ? $newValue : [];
        $oldValue = is_array($oldValue) ? $oldValue : [];

        if (Str::isUuid($lastPathSegment)) {
            if (isset($newValue['name'])) {
                // Set/update it
                $this->projectConfig->setNameMapping($lastPathSegment, $newValue['name']);
            } elseif (isset($oldValue['name'])) {
                // Remove it
                $this->projectConfig->removeNameMapping($lastPathSegment);
            }
        }

        $keys = array_unique(array_merge(
            array_keys($newValue),
            array_keys($oldValue),
        ));

        foreach ($keys as $key) {
            $this->updateContainedProjectConfigNames($key, $oldValue[$key] ?? [], $newValue[$key] ?? []);
        }
    }
}
