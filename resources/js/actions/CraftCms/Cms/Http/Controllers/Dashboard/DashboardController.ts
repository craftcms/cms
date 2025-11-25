import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\DashboardController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/DashboardController.php:26
* @route '/admin/dashboard'
*/
const DashboardController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: DashboardController.url(options),
    method: 'get',
})

DashboardController.definition = {
    methods: ["get","head"],
    url: '/admin/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\DashboardController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/DashboardController.php:26
* @route '/admin/dashboard'
*/
DashboardController.url = (options?: RouteQueryOptions) => {
    return DashboardController.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\DashboardController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/DashboardController.php:26
* @route '/admin/dashboard'
*/
DashboardController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: DashboardController.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\DashboardController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/DashboardController.php:26
* @route '/admin/dashboard'
*/
DashboardController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: DashboardController.url(options),
    method: 'head',
})

export default DashboardController