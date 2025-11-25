import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SiteGroupsController::showGroupRenameField
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SiteGroupsController.php:26
* @route '/admin/actions/sites/rename-group-field'
*/
export const showGroupRenameField = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: showGroupRenameField.url(options),
    method: 'post',
})

showGroupRenameField.definition = {
    methods: ["post"],
    url: '/admin/actions/sites/rename-group-field',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SiteGroupsController::showGroupRenameField
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SiteGroupsController.php:26
* @route '/admin/actions/sites/rename-group-field'
*/
showGroupRenameField.url = (options?: RouteQueryOptions) => {
    return showGroupRenameField.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SiteGroupsController::showGroupRenameField
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SiteGroupsController.php:26
* @route '/admin/actions/sites/rename-group-field'
*/
showGroupRenameField.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: showGroupRenameField.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SiteGroupsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SiteGroupsController.php:45
* @route '/admin/actions/sites/save-group'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/actions/sites/save-group',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SiteGroupsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SiteGroupsController.php:45
* @route '/admin/actions/sites/save-group'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SiteGroupsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SiteGroupsController.php:45
* @route '/admin/actions/sites/save-group'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SiteGroupsController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SiteGroupsController.php:57
* @route '/admin/actions/sites/delete-group'
*/
export const destroy = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: destroy.url(options),
    method: 'post',
})

destroy.definition = {
    methods: ["post"],
    url: '/admin/actions/sites/delete-group',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SiteGroupsController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SiteGroupsController.php:57
* @route '/admin/actions/sites/delete-group'
*/
destroy.url = (options?: RouteQueryOptions) => {
    return destroy.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SiteGroupsController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SiteGroupsController.php:57
* @route '/admin/actions/sites/delete-group'
*/
destroy.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: destroy.url(options),
    method: 'post',
})

const SiteGroupsController = { showGroupRenameField, store, destroy }

export default SiteGroupsController