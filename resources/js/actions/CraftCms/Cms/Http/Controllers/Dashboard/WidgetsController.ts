import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/WidgetsController.php:29
* @route '/admin/actions/dashboard/create-widget'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/actions/dashboard/create-widget',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/WidgetsController.php:29
* @route '/admin/actions/dashboard/create-widget'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/WidgetsController.php:29
* @route '/admin/actions/dashboard/create-widget'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController::update
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/WidgetsController.php:59
* @route '/admin/actions/dashboard/save-widget-settings'
*/
export const update = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(options),
    method: 'post',
})

update.definition = {
    methods: ["post"],
    url: '/admin/actions/dashboard/save-widget-settings',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController::update
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/WidgetsController.php:59
* @route '/admin/actions/dashboard/save-widget-settings'
*/
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController::update
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/WidgetsController.php:59
* @route '/admin/actions/dashboard/save-widget-settings'
*/
update.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController::deleteMethod
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/WidgetsController.php:120
* @route '/admin/actions/dashboard/delete-user-widget'
*/
export const deleteMethod = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deleteMethod.url(options),
    method: 'post',
})

deleteMethod.definition = {
    methods: ["post"],
    url: '/admin/actions/dashboard/delete-user-widget',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController::deleteMethod
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/WidgetsController.php:120
* @route '/admin/actions/dashboard/delete-user-widget'
*/
deleteMethod.url = (options?: RouteQueryOptions) => {
    return deleteMethod.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController::deleteMethod
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/WidgetsController.php:120
* @route '/admin/actions/dashboard/delete-user-widget'
*/
deleteMethod.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deleteMethod.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController::updateColspan
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/WidgetsController.php:86
* @route '/admin/actions/dashboard/change-widget-colspan'
*/
export const updateColspan = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateColspan.url(options),
    method: 'post',
})

updateColspan.definition = {
    methods: ["post"],
    url: '/admin/actions/dashboard/change-widget-colspan',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController::updateColspan
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/WidgetsController.php:86
* @route '/admin/actions/dashboard/change-widget-colspan'
*/
updateColspan.url = (options?: RouteQueryOptions) => {
    return updateColspan.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController::updateColspan
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/WidgetsController.php:86
* @route '/admin/actions/dashboard/change-widget-colspan'
*/
updateColspan.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateColspan.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController::reorder
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/WidgetsController.php:102
* @route '/admin/actions/dashboard/reorder-user-widgets'
*/
export const reorder = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reorder.url(options),
    method: 'post',
})

reorder.definition = {
    methods: ["post"],
    url: '/admin/actions/dashboard/reorder-user-widgets',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController::reorder
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/WidgetsController.php:102
* @route '/admin/actions/dashboard/reorder-user-widgets'
*/
reorder.url = (options?: RouteQueryOptions) => {
    return reorder.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController::reorder
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/WidgetsController.php:102
* @route '/admin/actions/dashboard/reorder-user-widgets'
*/
reorder.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reorder.url(options),
    method: 'post',
})

const WidgetsController = { store, update, deleteMethod, updateColspan, reorder, delete: deleteMethod }

export default WidgetsController