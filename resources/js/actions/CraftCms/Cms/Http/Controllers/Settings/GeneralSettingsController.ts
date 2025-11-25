import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/GeneralSettingsController.php:25
* @route '/admin/settings/general'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/settings/general',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/GeneralSettingsController.php:25
* @route '/admin/settings/general'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/GeneralSettingsController.php:25
* @route '/admin/settings/general'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/GeneralSettingsController.php:25
* @route '/admin/settings/general'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/GeneralSettingsController.php:44
* @route '/admin/settings/general'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/settings/general',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/GeneralSettingsController.php:44
* @route '/admin/settings/general'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/GeneralSettingsController.php:44
* @route '/admin/settings/general'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

const GeneralSettingsController = { index, store }

export default GeneralSettingsController