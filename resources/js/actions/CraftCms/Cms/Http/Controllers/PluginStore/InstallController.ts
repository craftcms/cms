import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:75
* @route '/admin/actions/pluginstore/install'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
})

index.definition = {
    methods: ["post"],
    url: '/admin/actions/pluginstore/install',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:75
* @route '/admin/actions/pluginstore/install'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:75
* @route '/admin/actions/pluginstore/install'
*/
index.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::craftInstall
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:45
* @route '/admin/actions/pluginstore/install/craft-install'
*/
export const craftInstall = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: craftInstall.url(options),
    method: 'post',
})

craftInstall.definition = {
    methods: ["post"],
    url: '/admin/actions/pluginstore/install/craft-install',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::craftInstall
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:45
* @route '/admin/actions/pluginstore/install/craft-install'
*/
craftInstall.url = (options?: RouteQueryOptions) => {
    return craftInstall.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::craftInstall
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:45
* @route '/admin/actions/pluginstore/install/craft-install'
*/
craftInstall.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: craftInstall.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::enable
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:76
* @route '/admin/actions/pluginstore/install/enable'
*/
export const enable = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: enable.url(options),
    method: 'post',
})

enable.definition = {
    methods: ["post"],
    url: '/admin/actions/pluginstore/install/enable',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::enable
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:76
* @route '/admin/actions/pluginstore/install/enable'
*/
enable.url = (options?: RouteQueryOptions) => {
    return enable.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::enable
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:76
* @route '/admin/actions/pluginstore/install/enable'
*/
enable.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: enable.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::migrate
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:87
* @route '/admin/actions/pluginstore/install/migrate'
*/
export const migrate = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: migrate.url(options),
    method: 'post',
})

migrate.definition = {
    methods: ["post"],
    url: '/admin/actions/pluginstore/install/migrate',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::migrate
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:87
* @route '/admin/actions/pluginstore/install/migrate'
*/
migrate.url = (options?: RouteQueryOptions) => {
    return migrate.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::migrate
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:87
* @route '/admin/actions/pluginstore/install/migrate'
*/
migrate.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: migrate.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::precheck
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:95
* @route '/admin/actions/pluginstore/install/precheck'
*/
export const precheck = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: precheck.url(options),
    method: 'post',
})

precheck.definition = {
    methods: ["post"],
    url: '/admin/actions/pluginstore/install/precheck',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::precheck
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:95
* @route '/admin/actions/pluginstore/install/precheck'
*/
precheck.url = (options?: RouteQueryOptions) => {
    return precheck.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::precheck
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:95
* @route '/admin/actions/pluginstore/install/precheck'
*/
precheck.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: precheck.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::recheckComposer
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:139
* @route '/admin/actions/pluginstore/install/recheck-composer'
*/
export const recheckComposer = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: recheckComposer.url(options),
    method: 'post',
})

recheckComposer.definition = {
    methods: ["post"],
    url: '/admin/actions/pluginstore/install/recheck-composer',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::recheckComposer
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:139
* @route '/admin/actions/pluginstore/install/recheck-composer'
*/
recheckComposer.url = (options?: RouteQueryOptions) => {
    return recheckComposer.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::recheckComposer
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:139
* @route '/admin/actions/pluginstore/install/recheck-composer'
*/
recheckComposer.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: recheckComposer.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::composerInstall
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:144
* @route '/admin/actions/pluginstore/install/composer-install'
*/
export const composerInstall = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: composerInstall.url(options),
    method: 'post',
})

composerInstall.definition = {
    methods: ["post"],
    url: '/admin/actions/pluginstore/install/composer-install',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::composerInstall
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:144
* @route '/admin/actions/pluginstore/install/composer-install'
*/
composerInstall.url = (options?: RouteQueryOptions) => {
    return composerInstall.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::composerInstall
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:144
* @route '/admin/actions/pluginstore/install/composer-install'
*/
composerInstall.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: composerInstall.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::composerRemove
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:172
* @route '/admin/actions/pluginstore/install/composer-remove'
*/
export const composerRemove = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: composerRemove.url(options),
    method: 'post',
})

composerRemove.definition = {
    methods: ["post"],
    url: '/admin/actions/pluginstore/install/composer-remove',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::composerRemove
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:172
* @route '/admin/actions/pluginstore/install/composer-remove'
*/
composerRemove.url = (options?: RouteQueryOptions) => {
    return composerRemove.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::composerRemove
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:172
* @route '/admin/actions/pluginstore/install/composer-remove'
*/
composerRemove.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: composerRemove.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::finish
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:195
* @route '/admin/actions/pluginstore/install/finish'
*/
export const finish = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: finish.url(options),
    method: 'post',
})

finish.definition = {
    methods: ["post"],
    url: '/admin/actions/pluginstore/install/finish',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::finish
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:195
* @route '/admin/actions/pluginstore/install/finish'
*/
finish.url = (options?: RouteQueryOptions) => {
    return finish.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\InstallController::finish
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/InstallController.php:195
* @route '/admin/actions/pluginstore/install/finish'
*/
finish.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: finish.url(options),
    method: 'post',
})

const InstallController = { index, craftInstall, enable, migrate, precheck, recheckComposer, composerInstall, composerRemove, finish }

export default InstallController