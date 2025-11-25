import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ClearCachesController::clearCaches
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ClearCachesController.php:27
* @route '/admin/actions/utilities/clear-caches-perform-action'
*/
export const clearCaches = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: clearCaches.url(options),
    method: 'post',
})

clearCaches.definition = {
    methods: ["post"],
    url: '/admin/actions/utilities/clear-caches-perform-action',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ClearCachesController::clearCaches
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ClearCachesController.php:27
* @route '/admin/actions/utilities/clear-caches-perform-action'
*/
clearCaches.url = (options?: RouteQueryOptions) => {
    return clearCaches.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ClearCachesController::clearCaches
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ClearCachesController.php:27
* @route '/admin/actions/utilities/clear-caches-perform-action'
*/
clearCaches.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: clearCaches.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ClearCachesController::invalidateTags
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ClearCachesController.php:58
* @route '/admin/actions/utilities/invalidate-tags'
*/
export const invalidateTags = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: invalidateTags.url(options),
    method: 'post',
})

invalidateTags.definition = {
    methods: ["post"],
    url: '/admin/actions/utilities/invalidate-tags',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ClearCachesController::invalidateTags
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ClearCachesController.php:58
* @route '/admin/actions/utilities/invalidate-tags'
*/
invalidateTags.url = (options?: RouteQueryOptions) => {
    return invalidateTags.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ClearCachesController::invalidateTags
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ClearCachesController.php:58
* @route '/admin/actions/utilities/invalidate-tags'
*/
invalidateTags.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: invalidateTags.url(options),
    method: 'post',
})

const ClearCachesController = { clearCaches, invalidateTags }

export default ClearCachesController