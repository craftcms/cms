import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\Widgets\CraftSupportController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/Widgets/CraftSupportController.php:40
* @route '/admin/actions/dashboard/send-support-request'
*/
const CraftSupportController = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: CraftSupportController.url(options),
    method: 'post',
})

CraftSupportController.definition = {
    methods: ["post"],
    url: '/admin/actions/dashboard/send-support-request',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\Widgets\CraftSupportController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/Widgets/CraftSupportController.php:40
* @route '/admin/actions/dashboard/send-support-request'
*/
CraftSupportController.url = (options?: RouteQueryOptions) => {
    return CraftSupportController.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Dashboard\Widgets\CraftSupportController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Dashboard/Widgets/CraftSupportController.php:40
* @route '/admin/actions/dashboard/send-support-request'
*/
CraftSupportController.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: CraftSupportController.url(options),
    method: 'post',
})

export default CraftSupportController