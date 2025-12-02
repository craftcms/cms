import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserSettingsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserSettingsController.php:33
* @route '/admin/actions/user-settings/save-user-settings'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/actions/user-settings/save-user-settings',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserSettingsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserSettingsController.php:33
* @route '/admin/actions/user-settings/save-user-settings'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserSettingsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserSettingsController.php:33
* @route '/admin/actions/user-settings/save-user-settings'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserSettingsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserSettingsController.php:25
* @route '/admin/settings/users/settings'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/settings/users/settings',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserSettingsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserSettingsController.php:25
* @route '/admin/settings/users/settings'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserSettingsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserSettingsController.php:25
* @route '/admin/settings/users/settings'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserSettingsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserSettingsController.php:25
* @route '/admin/settings/users/settings'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const UserSettingsController = { store, index }

export default UserSettingsController