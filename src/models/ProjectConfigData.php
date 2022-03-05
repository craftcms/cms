<?php

declare(strict_types=1);
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
     * @param $oldValue
     * @param $newValue
     * @param string $path
     * @param bool $triggerUpdate
     * @param string|null $message
     * @param bool $force
     */
    public function commitChanges($oldValue, $newValue, string $path, bool $triggerUpdate = false, ?string $message = null, bool $force = false): void
    {
        if (!$force && !empty($this->parsedChanges[$path])) {
            return;
        }

        $this->parsedChanges[$path] = true;

        $projectConfig = Craft::$app->getProjectConfig();
        $valueChanged = $triggerUpdate || $projectConfig->forceUpdate || ProjectConfigHelper::encodeValueAsString($oldValue) !== ProjectConfigHelper::encodeValueAsString($newValue);

        if ($newValue === null && is_array($oldValue)) {
            $this->removeContainedProjectConfigNames(pathinfo($path, PATHINFO_EXTENSION), $oldValue);
        } elseif (is_array($newValue)) {
            $this->setContainedProjectConfigNames(pathinfo($path, PATHINFO_EXTENSION), $newValue);
        }

        if ($valueChanged && !$projectConfig->muteEvents) {
            $event = new ConfigEvent(compact('path', 'oldValue', 'newValue'));
            if ($newValue === null && $oldValue !== null) {
                // Fire a 'removeItem' event
                $projectConfig->trigger(ProjectConfigService::EVENT_REMOVE_ITEM, $event);
            } elseif ($oldValue === null && $newValue !== null) {
                // Fire an 'addItem' event
                $projectConfig->trigger(ProjectConfigService::EVENT_ADD_ITEM, $event);
            } else {
                // Fire an 'updateItem' event
                $projectConfig->trigger(ProjectConfigService::EVENT_UPDATE_ITEM, $event);
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
            $projectConfig->rememberAppliedChanges($path, $oldValue, $newValue, $message);
            $this->setInternal($path, $newValue);
            $projectConfig->updateStoredConfigAfterRequest();

            if ($projectConfig->writeYamlAutomatically) {
                $projectConfig->updateParsedConfigTimesAfterRequest();
            }
        }
    }

    /**
     * Update the internal data storage.
     *
     * @param $path
     * @param $value
     */
    protected function setInternal($path, $value): void
    {
        if ($value === null) {
            $this->delete($path);
        }

        $this->traverseDataArray($this->data, $path, $value);
    }

    /**
     * Delete a path from the internal data storage.
     *
     * @param $path
     * @return mixed|null
     */
    protected function delete($path): mixed
    {
        return $this->traverseDataArray($this->data, $path, null, true);
    }

    /**
     * Get a list of all the project name changes.
     *
     * @return array
     */
    public function getProjectConfigNameChanges(): array
    {
        return $this->projectConfigNameChanges;
    }

    /**
     * Set all the contained project config names to the buffer.
     *
     * @param string $lastPathSegment
     * @param array $data
     */
    protected function setContainedProjectConfigNames(string $lastPathSegment, array $data): void
    {
        if (preg_match('/^' . StringHelper::UUID_PATTERN . '$/i', $lastPathSegment) && isset($data['name'])) {
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
     */
    protected function removeContainedProjectConfigNames(string $lastPathSegment, array $data): void
    {
        if (preg_match('/^' . StringHelper::UUID_PATTERN . '$/i', $lastPathSegment)) {
            $this->projectConfigNameChanges[$lastPathSegment] = null;
        }

        foreach ($data as $key => $value) {
            // Traverse further
            if (is_array($value)) {
                $this->setContainedProjectConfigNames($key, $value);
            }
        }
    }
}
