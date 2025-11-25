import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\Widgets\FeedController::cacheData
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/Widgets/FeedController.php:14
* @route '/admin/actions/dashboard/cache-feed-data'
*/
export const cacheData = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cacheData.url(options),
    method: 'post',
})

cacheData.definition = {
    methods: ["post"],
    url: '/admin/actions/dashboard/cache-feed-data',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\Widgets\FeedController::cacheData
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/Widgets/FeedController.php:14
* @route '/admin/actions/dashboard/cache-feed-data'
*/
cacheData.url = (options?: RouteQueryOptions) => {
    return cacheData.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\Widgets\FeedController::cacheData
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/Widgets/FeedController.php:14
* @route '/admin/actions/dashboard/cache-feed-data'
*/
cacheData.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cacheData.url(options),
    method: 'post',
})

const FeedController = { cacheData }

export default FeedController