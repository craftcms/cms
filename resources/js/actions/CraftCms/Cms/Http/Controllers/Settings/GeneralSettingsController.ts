import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/GeneralSettingsController.php:43
* @route '/admin/actions/system-settings/save-general-settings'
*/
const store14f08f2e4b841632ad4ac7947c35503b = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store14f08f2e4b841632ad4ac7947c35503b.url(options),
    method: 'post',
})

store14f08f2e4b841632ad4ac7947c35503b.definition = {
    methods: ["post"],
    url: '/admin/actions/system-settings/save-general-settings',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/GeneralSettingsController.php:43
* @route '/admin/actions/system-settings/save-general-settings'
*/
store14f08f2e4b841632ad4ac7947c35503b.url = (options?: RouteQueryOptions) => {
    return store14f08f2e4b841632ad4ac7947c35503b.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/GeneralSettingsController.php:43
* @route '/admin/actions/system-settings/save-general-settings'
*/
store14f08f2e4b841632ad4ac7947c35503b.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store14f08f2e4b841632ad4ac7947c35503b.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/GeneralSettingsController.php:43
* @route '/admin/settings/general'
*/
const store44865ed91750c2ecf2425d92e3890a74 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store44865ed91750c2ecf2425d92e3890a74.url(options),
    method: 'post',
})

store44865ed91750c2ecf2425d92e3890a74.definition = {
    methods: ["post"],
    url: '/admin/settings/general',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/GeneralSettingsController.php:43
* @route '/admin/settings/general'
*/
store44865ed91750c2ecf2425d92e3890a74.url = (options?: RouteQueryOptions) => {
    return store44865ed91750c2ecf2425d92e3890a74.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/GeneralSettingsController.php:43
* @route '/admin/settings/general'
*/
store44865ed91750c2ecf2425d92e3890a74.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store44865ed91750c2ecf2425d92e3890a74.url(options),
    method: 'post',
})

export const store = {
    '/admin/actions/system-settings/save-general-settings': store14f08f2e4b841632ad4ac7947c35503b,
    '/admin/settings/general': store44865ed91750c2ecf2425d92e3890a74,
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/GeneralSettingsController.php:24
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
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/GeneralSettingsController.php:24
* @route '/admin/settings/general'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/GeneralSettingsController.php:24
* @route '/admin/settings/general'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/GeneralSettingsController.php:24
* @route '/admin/settings/general'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const GeneralSettingsController = { store, index }

export default GeneralSettingsController