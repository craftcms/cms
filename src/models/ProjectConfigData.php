<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\events\ConfigEvent;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\services\ProjectConfig as ProjectConfigService;

/**
 * ProjectConfigData model class represents a modifiable instance of a project config data structure that can be modified
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ProjectConfigData extends ReadOnlyProjectConfigData
{
    /**
     * @var array Holds the already parsed paths as keys.
     */
    protected array $parsedChanges = [];

    /**
     * @var array Keeps track of all the project config name changes.
     * @deprecated in 4.4.17
     */
    protected array $projectConfigNameChanges = [];

    /**
     * Return true if a path has been modified over the lifetime of this project config set.
     *
     * @param string $path
     * @return bool
     */
    public function getHasPathBeenModified(string $path): bool
    {
        return array_key_exists($path, $this->parsedChanges);
    }

    /**
     * Commit changes by firing the appropriate events and updating the appropriate storages.
     *
     * @param mixed $oldValue
     * @param mixed $newValue
     * @param string $path
     * @param bool $triggerUpdate
     * @param string|null $message
     * @param bool $force
     */
    public function commitChanges(mixed $oldValue, mixed $newValue, string $path, bool $triggerUpdate = false, ?string $message = null, bool $force = false): void
    {
        if (!$force && !empty($this->parsedChanges[$path])) {
            return;
        }

        $this->parsedChanges[$path] = true;

        $valueChanged = (
            $triggerUpdate ||
            $this->projectConfig->forceUpdate ||
            ProjectConfigHelper::encodeValueAsString($oldValue) !== ProjectConfigHelper::encodeValueAsString($newValue)
        );

        if ($valueChanged || $force) {
            $this->updateContainedProjectConfigNames(pathinfo($path, PATHINFO_EXTENSION), $oldValue, $newValue);
        }

        if ($valueChanged) {
            if (!$this->projectConfig->muteEvents) {
                $event = new ConfigEvent(compact('path', 'oldValue', 'newValue'));
                if ($newValue === null && $oldValue !== null) {
                    // Fire a 'removeItem' event
                    $this->projectConfig->trigger(ProjectConfigService::EVENT_REMOVE_ITEM, $event);
                } elseif ($oldValue === null && $newValue !== null) {
                    // Fire an 'addItem' event
                    $this->projectConfig->trigger(ProjectConfigService::EVENT_ADD_ITEM, $event);
                } else {
                    // Fire an 'updateItem' event
                    $this->projectConfig->trigger(ProjectConfigService::EVENT_UPDATE_ITEM, $event);
                }
            }
        }

        // Mark this path, and any parent paths, as parsed
        $tok = strtok($path, '.');
        $thisPath = '';
        while ($tok !== false) {
            $thisPath .= ($thisPath !== '' ? '.' : '') . $tok;
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
     * @param string|string[] $path
     * @param mixed $value
     */
    protected function setInternal(string|array $path, mixed $value): void
    {
        if ($value === null) {
            $this->delete($path);
        }

        ProjectConfigHelper::traverseDataArray($this->data, $path, $value);
    }

    /**
     * Delete a path from the internal data storage.
     *
     * @param string|string[] $path
     * @return mixed
     */
    protected function delete(string|array $path): mixed
    {
        ProjectConfigHelper::traverseDataArray($this->data, $path, null, true);

        return null;
    }

    /**
     * Get a list of all the project name changes.
     *
     * @return array
     * @deprecated in 4.4.17
     */
    public function getProjectConfigNameChanges(): array
    {
        return $this->projectConfigNameChanges;
    }

    private function updateContainedProjectConfigNames(string $lastPathSegment, mixed $oldValue, mixed $newValue): void
    {
        // Normalize both values to arrays
        $newValue = is_array($newValue) ? $newValue : [];
        $oldValue = is_array($oldValue) ? $oldValue : [];

        if (StringHelper::isUUID($lastPathSegment)) {
            if (isset($newValue['name'])) {
                // Set/update it
                $this->projectConfig->setNameMapping($lastPathSegment, $newValue['name']);
                $this->projectConfigNameChanges[$lastPathSegment] = $newValue['name'];
            } elseif (isset($oldValue['name'])) {
                // Remove it
                $this->projectConfig->removeNameMapping($lastPathSegment);
                $this->projectConfigNameChanges[$lastPathSegment] = null;
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

    /**
     * Set all the contained project config names to the buffer.
     *
     * @param string $lastPathSegment
     * @param array $data
     * @deprecated in 4.4.17
     */
    protected function setContainedProjectConfigNames(string $lastPathSegment, array $data): void
    {
        if (preg_match('/^' . StringHelper::UUID_PATTERN . '$/i', $lastPathSegment) && isset($data['name'])) {
            Craft::$app->getProjectConfig()->setNameMapping($lastPathSegment, $data['name']);
            $this->projectConfigNameChanges[$lastPathSegment] = $data['name'];
        }

        foreach ($data as $key => $value) {
            // Traverse further
            if (is_array($value)) {
                $this->setContainedProjectConfigNames((string)$key, $value);
            }
        }
    }

    /**
     * Mark any contained project config names for removal.
     *
     * @param string $lastPathSegment
     * @param array $data
     * @deprecated in 4.4.17
     */
    protected function removeContainedProjectConfigNames(string $lastPathSegment, array $data): void
    {
        if (preg_match('/^' . StringHelper::UUID_PATTERN . '$/i', $lastPathSegment)) {
            Craft::$app->getProjectConfig()->removeNameMapping($lastPathSegment);
            $this->projectConfigNameChanges[$lastPathSegment] = null;
        }

        foreach ($data as $key => $value) {
            // Traverse further
            if (is_array($value)) {
                $this->removeContainedProjectConfigNames($key, $value);
            }
        }
    }
}
