import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdatesController::check
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdatesController.php:36
* @route '/admin/actions/app/check-for-updates'
*/
export const check = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: check.url(options),
    method: 'post',
})

check.definition = {
    methods: ["post"],
    url: '/admin/actions/app/check-for-updates',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdatesController::check
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdatesController.php:36
* @route '/admin/actions/app/check-for-updates'
*/
check.url = (options?: RouteQueryOptions) => {
    return check.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdatesController::check
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdatesController.php:36
* @route '/admin/actions/app/check-for-updates'
*/
check.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: check.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdatesController::cache
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdatesController.php:47
* @route '/admin/actions/app/cache-updates'
*/
export const cache = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cache.url(options),
    method: 'post',
})

cache.definition = {
    methods: ["post"],
    url: '/admin/actions/app/cache-updates',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdatesController::cache
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdatesController.php:47
* @route '/admin/actions/app/cache-updates'
*/
cache.url = (options?: RouteQueryOptions) => {
    return cache.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdatesController::cache
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdatesController.php:47
* @route '/admin/actions/app/cache-updates'
*/
cache.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cache.url(options),
    method: 'post',
})

const UpdatesController = { check, cache }

export default UpdatesController