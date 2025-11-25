import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Settings\RoutesController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/RoutesController.php:35
* @route '/admin/actions/routes/save-route'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/actions/routes/save-route',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\RoutesController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/RoutesController.php:35
* @route '/admin/actions/routes/save-route'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\RoutesController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/RoutesController.php:35
* @route '/admin/actions/routes/save-route'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\RoutesController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/RoutesController.php:45
* @route '/admin/actions/routes/delete-route'
*/
export const destroy = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: destroy.url(options),
    method: 'post',
})

destroy.definition = {
    methods: ["post"],
    url: '/admin/actions/routes/delete-route',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\RoutesController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/RoutesController.php:45
* @route '/admin/actions/routes/delete-route'
*/
destroy.url = (options?: RouteQueryOptions) => {
    return destroy.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\RoutesController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/RoutesController.php:45
* @route '/admin/actions/routes/delete-route'
*/
destroy.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: destroy.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\RoutesController::reorder
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/RoutesController.php:56
* @route '/admin/actions/routes/update-route-order'
*/
export const reorder = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reorder.url(options),
    method: 'post',
})

reorder.definition = {
    methods: ["post"],
    url: '/admin/actions/routes/update-route-order',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\RoutesController::reorder
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/RoutesController.php:56
* @route '/admin/actions/routes/update-route-order'
*/
reorder.url = (options?: RouteQueryOptions) => {
    return reorder.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\RoutesController::reorder
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/RoutesController.php:56
* @route '/admin/actions/routes/update-route-order'
*/
reorder.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reorder.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\RoutesController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/RoutesController.php:24
* @route '/admin/settings/routes'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/settings/routes',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\RoutesController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/RoutesController.php:24
* @route '/admin/settings/routes'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\RoutesController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/RoutesController.php:24
* @route '/admin/settings/routes'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\RoutesController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/RoutesController.php:24
* @route '/admin/settings/routes'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const RoutesController = { store, destroy, reorder, index }

export default RoutesController