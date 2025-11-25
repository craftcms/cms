import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:75
* @route '/admin/actions/config-sync'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
})

index.definition = {
    methods: ["post"],
    url: '/admin/actions/config-sync',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:75
* @route '/admin/actions/config-sync'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:75
* @route '/admin/actions/config-sync'
*/
index.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::retry
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:51
* @route '/admin/actions/config-sync/retry'
*/
export const retry = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: retry.url(options),
    method: 'post',
})

retry.definition = {
    methods: ["post"],
    url: '/admin/actions/config-sync/retry',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::retry
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:51
* @route '/admin/actions/config-sync/retry'
*/
retry.url = (options?: RouteQueryOptions) => {
    return retry.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::retry
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:51
* @route '/admin/actions/config-sync/retry'
*/
retry.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: retry.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::applyYamlChanges
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:59
* @route '/admin/actions/config-sync/apply-yaml-changes'
*/
export const applyYamlChanges = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyYamlChanges.url(options),
    method: 'post',
})

applyYamlChanges.definition = {
    methods: ["post"],
    url: '/admin/actions/config-sync/apply-yaml-changes',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::applyYamlChanges
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:59
* @route '/admin/actions/config-sync/apply-yaml-changes'
*/
applyYamlChanges.url = (options?: RouteQueryOptions) => {
    return applyYamlChanges.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::applyYamlChanges
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:59
* @route '/admin/actions/config-sync/apply-yaml-changes'
*/
applyYamlChanges.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyYamlChanges.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::regenerateYaml
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:83
* @route '/admin/actions/config-sync/regenerate-yaml'
*/
export const regenerateYaml = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: regenerateYaml.url(options),
    method: 'post',
})

regenerateYaml.definition = {
    methods: ["post"],
    url: '/admin/actions/config-sync/regenerate-yaml',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::regenerateYaml
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:83
* @route '/admin/actions/config-sync/regenerate-yaml'
*/
regenerateYaml.url = (options?: RouteQueryOptions) => {
    return regenerateYaml.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::regenerateYaml
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:83
* @route '/admin/actions/config-sync/regenerate-yaml'
*/
regenerateYaml.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: regenerateYaml.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::uninstallPlugin
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:90
* @route '/admin/actions/config-sync/uninstall-plugin'
*/
export const uninstallPlugin = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uninstallPlugin.url(options),
    method: 'post',
})

uninstallPlugin.definition = {
    methods: ["post"],
    url: '/admin/actions/config-sync/uninstall-plugin',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::uninstallPlugin
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:90
* @route '/admin/actions/config-sync/uninstall-plugin'
*/
uninstallPlugin.url = (options?: RouteQueryOptions) => {
    return uninstallPlugin.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::uninstallPlugin
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:90
* @route '/admin/actions/config-sync/uninstall-plugin'
*/
uninstallPlugin.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uninstallPlugin.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::installPlugin
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:98
* @route '/admin/actions/config-sync/install-plugin'
*/
export const installPlugin = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: installPlugin.url(options),
    method: 'post',
})

installPlugin.definition = {
    methods: ["post"],
    url: '/admin/actions/config-sync/install-plugin',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::installPlugin
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:98
* @route '/admin/actions/config-sync/install-plugin'
*/
installPlugin.url = (options?: RouteQueryOptions) => {
    return installPlugin.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::installPlugin
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:98
* @route '/admin/actions/config-sync/install-plugin'
*/
installPlugin.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: installPlugin.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::precheck
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:95
* @route '/admin/actions/config-sync/precheck'
*/
export const precheck = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: precheck.url(options),
    method: 'post',
})

precheck.definition = {
    methods: ["post"],
    url: '/admin/actions/config-sync/precheck',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::precheck
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:95
* @route '/admin/actions/config-sync/precheck'
*/
precheck.url = (options?: RouteQueryOptions) => {
    return precheck.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::precheck
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:95
* @route '/admin/actions/config-sync/precheck'
*/
precheck.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: precheck.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::recheckComposer
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:139
* @route '/admin/actions/config-sync/recheck-composer'
*/
export const recheckComposer = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: recheckComposer.url(options),
    method: 'post',
})

recheckComposer.definition = {
    methods: ["post"],
    url: '/admin/actions/config-sync/recheck-composer',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::recheckComposer
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:139
* @route '/admin/actions/config-sync/recheck-composer'
*/
recheckComposer.url = (options?: RouteQueryOptions) => {
    return recheckComposer.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::recheckComposer
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:139
* @route '/admin/actions/config-sync/recheck-composer'
*/
recheckComposer.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: recheckComposer.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::composerInstall
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:144
* @route '/admin/actions/config-sync/composer-install'
*/
export const composerInstall = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: composerInstall.url(options),
    method: 'post',
})

composerInstall.definition = {
    methods: ["post"],
    url: '/admin/actions/config-sync/composer-install',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::composerInstall
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:144
* @route '/admin/actions/config-sync/composer-install'
*/
composerInstall.url = (options?: RouteQueryOptions) => {
    return composerInstall.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::composerInstall
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:144
* @route '/admin/actions/config-sync/composer-install'
*/
composerInstall.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: composerInstall.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::composerRemove
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:172
* @route '/admin/actions/config-sync/composer-remove'
*/
export const composerRemove = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: composerRemove.url(options),
    method: 'post',
})

composerRemove.definition = {
    methods: ["post"],
    url: '/admin/actions/config-sync/composer-remove',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::composerRemove
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:172
* @route '/admin/actions/config-sync/composer-remove'
*/
composerRemove.url = (options?: RouteQueryOptions) => {
    return composerRemove.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::composerRemove
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:172
* @route '/admin/actions/config-sync/composer-remove'
*/
composerRemove.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: composerRemove.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::finish
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:195
* @route '/admin/actions/config-sync/finish'
*/
export const finish = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: finish.url(options),
    method: 'post',
})

finish.definition = {
    methods: ["post"],
    url: '/admin/actions/config-sync/finish',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::finish
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:195
* @route '/admin/actions/config-sync/finish'
*/
finish.url = (options?: RouteQueryOptions) => {
    return finish.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\ConfigSyncController::finish
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ConfigSyncController.php:195
* @route '/admin/actions/config-sync/finish'
*/
finish.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: finish.url(options),
    method: 'post',
})

const ConfigSyncController = { index, retry, applyYamlChanges, regenerateYaml, uninstallPlugin, installPlugin, precheck, recheckComposer, composerInstall, composerRemove, finish }

export default ConfigSyncController