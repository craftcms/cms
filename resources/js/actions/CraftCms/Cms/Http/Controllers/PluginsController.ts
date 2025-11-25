import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::install
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:50
* @route '/admin/actions/plugins/install-plugin'
*/
export const install = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: install.url(options),
    method: 'post',
})

install.definition = {
    methods: ["post"],
    url: '/admin/actions/plugins/install-plugin',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::install
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:50
* @route '/admin/actions/plugins/install-plugin'
*/
install.url = (options?: RouteQueryOptions) => {
    return install.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::install
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:50
* @route '/admin/actions/plugins/install-plugin'
*/
install.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: install.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::uninstall
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:79
* @route '/admin/actions/plugins/uninstall-plugin'
*/
export const uninstall = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uninstall.url(options),
    method: 'post',
})

uninstall.definition = {
    methods: ["post"],
    url: '/admin/actions/plugins/uninstall-plugin',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::uninstall
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:79
* @route '/admin/actions/plugins/uninstall-plugin'
*/
uninstall.url = (options?: RouteQueryOptions) => {
    return uninstall.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::uninstall
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:79
* @route '/admin/actions/plugins/uninstall-plugin'
*/
uninstall.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uninstall.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::switchEdition
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:67
* @route '/admin/actions/plugins/switch-edition'
*/
export const switchEdition = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: switchEdition.url(options),
    method: 'post',
})

switchEdition.definition = {
    methods: ["post"],
    url: '/admin/actions/plugins/switch-edition',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::switchEdition
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:67
* @route '/admin/actions/plugins/switch-edition'
*/
switchEdition.url = (options?: RouteQueryOptions) => {
    return switchEdition.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::switchEdition
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:67
* @route '/admin/actions/plugins/switch-edition'
*/
switchEdition.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: switchEdition.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::disable
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:105
* @route '/admin/actions/plugins/disable-plugin'
*/
export const disable = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: disable.url(options),
    method: 'post',
})

disable.definition = {
    methods: ["post"],
    url: '/admin/actions/plugins/disable-plugin',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::disable
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:105
* @route '/admin/actions/plugins/disable-plugin'
*/
disable.url = (options?: RouteQueryOptions) => {
    return disable.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::disable
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:105
* @route '/admin/actions/plugins/disable-plugin'
*/
disable.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: disable.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::enable
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:92
* @route '/admin/actions/plugins/enable-plugin'
*/
export const enable = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: enable.url(options),
    method: 'post',
})

enable.definition = {
    methods: ["post"],
    url: '/admin/actions/plugins/enable-plugin',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::enable
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:92
* @route '/admin/actions/plugins/enable-plugin'
*/
enable.url = (options?: RouteQueryOptions) => {
    return enable.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::enable
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:92
* @route '/admin/actions/plugins/enable-plugin'
*/
enable.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: enable.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::saveSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:142
* @route '/admin/actions/plugins/save-plugin-settings'
*/
export const saveSettings = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: saveSettings.url(options),
    method: 'post',
})

saveSettings.definition = {
    methods: ["post"],
    url: '/admin/actions/plugins/save-plugin-settings',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::saveSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:142
* @route '/admin/actions/plugins/save-plugin-settings'
*/
saveSettings.url = (options?: RouteQueryOptions) => {
    return saveSettings.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::saveSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:142
* @route '/admin/actions/plugins/save-plugin-settings'
*/
saveSettings.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: saveSettings.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:30
* @route '/admin/settings/plugins'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/settings/plugins',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:30
* @route '/admin/settings/plugins'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:30
* @route '/admin/settings/plugins'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:30
* @route '/admin/settings/plugins'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::editSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:118
* @route '/admin/settings/plugins/{handle}'
*/
export const editSettings = (args: { handle: string | number } | [handle: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editSettings.url(args, options),
    method: 'get',
})

editSettings.definition = {
    methods: ["get","head"],
    url: '/admin/settings/plugins/{handle}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::editSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:118
* @route '/admin/settings/plugins/{handle}'
*/
editSettings.url = (args: { handle: string | number } | [handle: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { handle: args }
    }

    if (Array.isArray(args)) {
        args = {
            handle: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        handle: args.handle,
    }

    return editSettings.definition.url
            .replace('{handle}', parsedArgs.handle.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::editSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:118
* @route '/admin/settings/plugins/{handle}'
*/
editSettings.get = (args: { handle: string | number } | [handle: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editSettings.url(args, options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginsController::editSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginsController.php:118
* @route '/admin/settings/plugins/{handle}'
*/
editSettings.head = (args: { handle: string | number } | [handle: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: editSettings.url(args, options),
    method: 'head',
})

const PluginsController = { install, uninstall, switchEdition, disable, enable, saveSettings, index, editSettings }

export default PluginsController