import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\RemoveController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/RemoveController.php:75
* @route '/admin/actions/pluginstore/remove'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
})

index.definition = {
    methods: ["post"],
    url: '/admin/actions/pluginstore/remove',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\RemoveController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/RemoveController.php:75
* @route '/admin/actions/pluginstore/remove'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\RemoveController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/RemoveController.php:75
* @route '/admin/actions/pluginstore/remove'
*/
index.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\RemoveController::precheck
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/RemoveController.php:95
* @route '/admin/actions/pluginstore/remove/precheck'
*/
export const precheck = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: precheck.url(options),
    method: 'post',
})

precheck.definition = {
    methods: ["post"],
    url: '/admin/actions/pluginstore/remove/precheck',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\RemoveController::precheck
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/RemoveController.php:95
* @route '/admin/actions/pluginstore/remove/precheck'
*/
precheck.url = (options?: RouteQueryOptions) => {
    return precheck.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\RemoveController::precheck
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/RemoveController.php:95
* @route '/admin/actions/pluginstore/remove/precheck'
*/
precheck.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: precheck.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\RemoveController::recheckComposer
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/RemoveController.php:139
* @route '/admin/actions/pluginstore/remove/recheck-composer'
*/
export const recheckComposer = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: recheckComposer.url(options),
    method: 'post',
})

recheckComposer.definition = {
    methods: ["post"],
    url: '/admin/actions/pluginstore/remove/recheck-composer',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\RemoveController::recheckComposer
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/RemoveController.php:139
* @route '/admin/actions/pluginstore/remove/recheck-composer'
*/
recheckComposer.url = (options?: RouteQueryOptions) => {
    return recheckComposer.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\RemoveController::recheckComposer
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/RemoveController.php:139
* @route '/admin/actions/pluginstore/remove/recheck-composer'
*/
recheckComposer.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: recheckComposer.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\RemoveController::composerInstall
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/RemoveController.php:144
* @route '/admin/actions/pluginstore/remove/composer-install'
*/
export const composerInstall = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: composerInstall.url(options),
    method: 'post',
})

composerInstall.definition = {
    methods: ["post"],
    url: '/admin/actions/pluginstore/remove/composer-install',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\RemoveController::composerInstall
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/RemoveController.php:144
* @route '/admin/actions/pluginstore/remove/composer-install'
*/
composerInstall.url = (options?: RouteQueryOptions) => {
    return composerInstall.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\RemoveController::composerInstall
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/RemoveController.php:144
* @route '/admin/actions/pluginstore/remove/composer-install'
*/
composerInstall.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: composerInstall.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\RemoveController::composerRemove
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/RemoveController.php:172
* @route '/admin/actions/pluginstore/remove/composer-remove'
*/
export const composerRemove = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: composerRemove.url(options),
    method: 'post',
})

composerRemove.definition = {
    methods: ["post"],
    url: '/admin/actions/pluginstore/remove/composer-remove',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\RemoveController::composerRemove
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/RemoveController.php:172
* @route '/admin/actions/pluginstore/remove/composer-remove'
*/
composerRemove.url = (options?: RouteQueryOptions) => {
    return composerRemove.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\RemoveController::composerRemove
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/RemoveController.php:172
* @route '/admin/actions/pluginstore/remove/composer-remove'
*/
composerRemove.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: composerRemove.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\RemoveController::finish
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/RemoveController.php:195
* @route '/admin/actions/pluginstore/remove/finish'
*/
export const finish = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: finish.url(options),
    method: 'post',
})

finish.definition = {
    methods: ["post"],
    url: '/admin/actions/pluginstore/remove/finish',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\RemoveController::finish
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/RemoveController.php:195
* @route '/admin/actions/pluginstore/remove/finish'
*/
finish.url = (options?: RouteQueryOptions) => {
    return finish.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\RemoveController::finish
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/RemoveController.php:195
* @route '/admin/actions/pluginstore/remove/finish'
*/
finish.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: finish.url(options),
    method: 'post',
})

const RemoveController = { index, precheck, recheckComposer, composerInstall, composerRemove, finish }

export default RemoveController