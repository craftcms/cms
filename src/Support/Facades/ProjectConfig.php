<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void flush()
 * @method static mixed get(string|null $path = null, bool $getFromExternalConfig = false)
 * @method static array find(callable $callback, bool $fromExternalConfig = false)
 * @method static bool set(string $path, mixed $value, string|null $message = null, bool $updateTimestamp = true, bool $force = false)
 * @method static void remove(string $path, string|null $message = null)
 * @method static void regenerateExternalConfig()
 * @method static void applyExternalChanges()
 * @method static bool isApplyingExternalChanges()
 * @method static void applyConfigChanges(array $configData)
 * @method static bool getDoesExternalConfigExist()
 * @method static bool areChangesPending(string|null $path = null, bool $force = false)
 * @method static void processConfigChanges(string $path, bool $force = false)
 * @method static void updateParsedConfigTimesAfterRequest()
 * @method static bool updateParsedConfigTimes()
 * @method static void saveModifiedConfigData()
 * @method static array getPendingChangeSummary()
 * @method static array getAppliedChanges()
 * @method static bool getAreConfigSchemaVersionsCompatible(array $issues = [])
 * @method static \CraftCms\Cms\ProjectConfig\ProjectConfig onAdd(string $path, callable $handler, mixed $data = null)
 * @method static \CraftCms\Cms\ProjectConfig\ProjectConfig onUpdate(string $path, callable $handler, mixed $data = null)
 * @method static \CraftCms\Cms\ProjectConfig\ProjectConfig onRemove(string $path, callable $handler, mixed $data = null)
 * @method static void defer(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event, callable $handler)
 * @method static void registerChangeEventHandler(string $event, string $path, callable $handler, mixed $data = null)
 * @method static void handleChangeEvent(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static void rebuild()
 * @method static void writeYamlFiles(bool $force = false)
 * @method static void setNameMapping(string $uid, string $name)
 * @method static void removeNameMapping(string $uid)
 * @method static bool getHadFileWriteIssues()
 * @method static void rememberAppliedChanges(string $path, mixed $oldValue, mixed $newValue, string|null $message = null)
 * @method static \CraftCms\DependencyAwareCache\Dependency\CallbackDependency getCacheDependency()
 *
 * @see \CraftCms\Cms\ProjectConfig\ProjectConfig
 */
final class ProjectConfig extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\ProjectConfig\ProjectConfig::class;
    }
}
