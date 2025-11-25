import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults, validateParameters } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController::craftData
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/PluginStoreController.php:62
* @route '/admin/actions/plugin-store/craft-data'
*/
export const craftData = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: craftData.url(options),
    method: 'get',
})

craftData.definition = {
    methods: ["get","head"],
    url: '/admin/actions/plugin-store/craft-data',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController::craftData
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/PluginStoreController.php:62
* @route '/admin/actions/plugin-store/craft-data'
*/
craftData.url = (options?: RouteQueryOptions) => {
    return craftData.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController::craftData
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/PluginStoreController.php:62
* @route '/admin/actions/plugin-store/craft-data'
*/
craftData.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: craftData.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController::craftData
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/PluginStoreController.php:62
* @route '/admin/actions/plugin-store/craft-data'
*/
craftData.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: craftData.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController::savePluginLicenseKeys
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/PluginStoreController.php:88
* @route '/admin/actions/plugin-store/save-plugin-license-keys'
*/
export const savePluginLicenseKeys = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: savePluginLicenseKeys.url(options),
    method: 'post',
})

savePluginLicenseKeys.definition = {
    methods: ["post"],
    url: '/admin/actions/plugin-store/save-plugin-license-keys',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController::savePluginLicenseKeys
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/PluginStoreController.php:88
* @route '/admin/actions/plugin-store/save-plugin-license-keys'
*/
savePluginLicenseKeys.url = (options?: RouteQueryOptions) => {
    return savePluginLicenseKeys.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController::savePluginLicenseKeys
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/PluginStoreController.php:88
* @route '/admin/actions/plugin-store/save-plugin-license-keys'
*/
savePluginLicenseKeys.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: savePluginLicenseKeys.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/PluginStoreController.php:32
* @route '/admin/plugin-store{any?}'
*/
export const index = (args?: { any?: string | number } | [any: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/plugin-store{any?}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/PluginStoreController.php:32
* @route '/admin/plugin-store{any?}'
*/
index.url = (args?: { any?: string | number } | [any: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { any: args }
    }

    if (Array.isArray(args)) {
        args = {
            any: args[0],
        }
    }

    args = applyUrlDefaults(args)

    validateParameters(args, [
        "any",
    ])

    const parsedArgs = {
        any: args?.any,
    }

    return index.definition.url
            .replace('{any?}', parsedArgs.any?.toString() ?? '')
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/PluginStoreController.php:32
* @route '/admin/plugin-store{any?}'
*/
index.get = (args?: { any?: string | number } | [any: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PluginStore/PluginStoreController.php:32
* @route '/admin/plugin-store{any?}'
*/
index.head = (args?: { any?: string | number } | [any: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

const PluginStoreController = { craftData, savePluginLicenseKeys, index }

export default PluginStoreController